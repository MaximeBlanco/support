<?php

namespace Tests\Feature;

use App\Actions\Tickets\AssignTicket;
use App\Actions\Tickets\CloseTicket;
use App\Actions\Tickets\CreateTicket;
use App\Actions\Tickets\ReopenTicket;
use App\Actions\Tickets\ResolveTicket;
use App\Actions\Tickets\StartTicketWork;
use App\Actions\Tickets\UnassignTicket;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Exceptions\IllegalTicketTransition;
use App\Jobs\ComputeTicketResolutionTime;
use App\Models\Ticket;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketLifecycleTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Notification::fake();
    }

    public function test_a_new_ticket_starts_open_with_a_reference_and_a_history_entry(): void
    {
        $requester = $this->requester();

        $ticket = app(CreateTicket::class)->handle(
            $requester,
            'Impossible de se connecter au VPN',
            'Le client VPN renvoie une erreur 812 depuis ce matin.',
            TicketPriority::High,
        );

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertMatchesRegularExpression('/^TCK-\d{5}$/', $ticket->reference);
        $this->assertSame($requester->getKey(), $ticket->requester_id);
        $this->assertNull($ticket->assignee_id);

        $this->assertDatabaseHas('ticket_status_changes', [
            'ticket_id' => $ticket->getKey(),
            'from_status' => null,
            'to_status' => TicketStatus::Open->value,
        ]);
    }

    public function test_assigning_a_ticket_moves_it_to_assigned_and_notifies_the_technician(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $technician = $this->technician();
        $manager = $this->manager();

        app(AssignTicket::class)->handle($ticket, $technician, $manager);

        $ticket->refresh();

        $this->assertSame(TicketStatus::Assigned, $ticket->status);
        $this->assertSame($technician->getKey(), $ticket->assignee_id);
        $this->assertNotNull($ticket->assigned_at);

        Notification::assertSentTo($technician, TicketAssignedNotification::class);
    }

    public function test_reassigning_an_assigned_ticket_keeps_its_status(): void
    {
        $first = $this->technician();
        $second = $this->technician();
        $ticket = Ticket::factory()->assignedTo($first)->create();

        app(AssignTicket::class)->handle($ticket, $second, $this->manager());

        $ticket->refresh();

        $this->assertSame(TicketStatus::Assigned, $ticket->status);
        $this->assertSame($second->getKey(), $ticket->assignee_id);
    }

    public function test_the_full_happy_path_walks_every_status(): void
    {
        $technician = $this->technician();
        $manager = $this->manager();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        app(AssignTicket::class)->handle($ticket, $technician, $manager);
        $this->assertSame(TicketStatus::Assigned, $ticket->refresh()->status);

        app(StartTicketWork::class)->handle($ticket, $technician);
        $this->assertSame(TicketStatus::InProgress, $ticket->refresh()->status);

        app(ResolveTicket::class)->handle($ticket, $technician);
        $this->assertSame(TicketStatus::Resolved, $ticket->refresh()->status);
        $this->assertNotNull($ticket->resolved_at);

        app(CloseTicket::class)->handle($ticket, $manager);
        $this->assertSame(TicketStatus::Closed, $ticket->refresh()->status);
        $this->assertNotNull($ticket->closed_at);

        $this->assertSame(
            [
                TicketStatus::Assigned,
                TicketStatus::InProgress,
                TicketStatus::Resolved,
                TicketStatus::Closed,
            ],
            $ticket->statusChanges()->oldest()->pluck('to_status')->all(),
        );
    }

    public function test_an_illegal_transition_is_refused_with_a_named_exception(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->expectException(IllegalTicketTransition::class);

        app(CloseTicket::class)->handle($ticket, $this->manager());
    }

    public function test_a_refused_transition_leaves_the_ticket_untouched(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        try {
            app(ResolveTicket::class)->handle($ticket, $this->technician());
        } catch (IllegalTicketTransition) {
            // expected
        }

        $ticket->refresh();

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->resolved_at);
        $this->assertSame(0, $ticket->statusChanges()->count());
    }

    public function test_the_illegal_transition_exception_carries_a_conflict_status(): void
    {
        $exception = IllegalTicketTransition::between(TicketStatus::Open, TicketStatus::Closed);

        $this->assertSame(409, $exception->getStatusCode());
        $this->assertSame(TicketStatus::Open, $exception->from);
        $this->assertSame(TicketStatus::Closed, $exception->to);
    }

    public function test_a_closed_ticket_accepts_no_transition_at_all(): void
    {
        $technician = $this->technician();
        $ticket = Ticket::factory()->closed($technician)->create();

        $this->expectException(IllegalTicketTransition::class);

        app(ReopenTicket::class)->handle($ticket, $technician);
    }

    public function test_unassigning_sends_the_ticket_back_to_open(): void
    {
        $technician = $this->technician();
        $ticket = Ticket::factory()->assignedTo($technician)->create();

        app(UnassignTicket::class)->handle($ticket, $this->manager());

        $ticket->refresh();

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->assignee_id);
        $this->assertNull($ticket->assigned_at);
    }

    public function test_reopening_a_resolved_ticket_clears_its_resolution(): void
    {
        $technician = $this->technician();
        $ticket = Ticket::factory()->resolved($technician)->create();

        app(ReopenTicket::class)->handle($ticket, $technician);

        $ticket->refresh();

        $this->assertSame(TicketStatus::InProgress, $ticket->status);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->resolved_within_target);
    }

    public function test_resolving_notifies_the_requester_and_queues_the_resolution_time_job(): void
    {
        Queue::fake();

        $technician = $this->technician();
        $ticket = Ticket::factory()->inProgress($technician)->create();

        app(ResolveTicket::class)->handle($ticket, $technician);

        Notification::assertSentTo($ticket->requester, TicketResolvedNotification::class);
        Queue::assertPushed(ComputeTicketResolutionTime::class);
    }

    public function test_the_resolution_time_job_compares_the_elapsed_time_to_the_priority_target(): void
    {
        $technician = $this->technician();

        $inTime = Ticket::factory()->inProgress($technician)->create([
            'priority' => TicketPriority::High,
            'created_at' => now()->subHours(3),
        ]);

        app(ResolveTicket::class)->handle($inTime, $technician);

        $this->assertTrue($inTime->refresh()->resolved_within_target);

        $tooLate = Ticket::factory()->inProgress($technician)->create([
            'priority' => TicketPriority::High,
            'created_at' => now()->subHours(30),
        ]);

        app(ResolveTicket::class)->handle($tooLate, $technician);

        $this->assertFalse($tooLate->refresh()->resolved_within_target);
    }
}
