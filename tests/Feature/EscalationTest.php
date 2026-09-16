<?php

namespace Tests\Feature;

use App\Actions\Tickets\EscalateOverdueTickets;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Notifications\TicketEscalatedNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class EscalationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Notification::fake();
    }

    public function test_a_ticket_past_its_target_moves_up_one_level(): void
    {
        $ticket = Ticket::factory()->priority(TicketPriority::Low)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(80),
        ]);

        app(EscalateOverdueTickets::class)->handle();

        $ticket->refresh();

        $this->assertSame(TicketPriority::Normal, $ticket->priority);
        $this->assertNotNull($ticket->escalated_at);
        $this->assertSame(1, $ticket->escalation_count);
    }

    public function test_a_ticket_still_within_its_target_is_left_alone(): void
    {
        $ticket = Ticket::factory()->priority(TicketPriority::Low)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(10),
        ]);

        app(EscalateOverdueTickets::class)->handle();

        $ticket->refresh();

        $this->assertSame(TicketPriority::Low, $ticket->priority);
        $this->assertNull($ticket->escalated_at);
        $this->assertSame(0, $ticket->escalation_count);
    }

    public function test_the_target_that_counts_is_the_one_of_the_ticket_priority(): void
    {
        $critical = Ticket::factory()->priority(TicketPriority::Critical)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(5),
        ]);

        $low = Ticket::factory()->priority(TicketPriority::Low)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(5),
        ]);

        $report = app(EscalateOverdueTickets::class)->handle();

        $this->assertSame(1, $report->examined);
        $this->assertTrue($report->flagged->pluck('id')->contains($critical->id));
        $this->assertSame(TicketPriority::Low, $low->refresh()->priority);
    }

    public function test_an_already_critical_ticket_is_flagged_but_not_raised(): void
    {
        $ticket = Ticket::factory()->priority(TicketPriority::Critical)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(9),
        ]);

        $report = app(EscalateOverdueTickets::class)->handle();

        $this->assertTrue($report->flagged->pluck('id')->contains($ticket->id));
        $this->assertTrue($report->escalated->isEmpty());
        $this->assertSame(TicketPriority::Critical, $ticket->refresh()->priority);
        $this->assertSame(1, $ticket->escalation_count);
    }

    public function test_running_it_twice_in_a_row_escalates_nothing_more(): void
    {
        Ticket::factory()->priority(TicketPriority::Low)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(80),
        ]);

        $first = app(EscalateOverdueTickets::class)->handle();
        $second = app(EscalateOverdueTickets::class)->handle();

        $this->assertSame(1, $first->escalated->count());
        $this->assertTrue($second->changedNothing());
        $this->assertSame(0, $second->examined);
    }

    public function test_a_resolved_ticket_is_never_escalated(): void
    {
        $ticket = Ticket::factory()->resolved($this->technician())->create([
            'priority' => TicketPriority::Low,
            'created_at' => now()->subHours(200),
        ]);

        app(EscalateOverdueTickets::class)->handle();

        $this->assertSame(TicketPriority::Low, $ticket->refresh()->priority);
    }

    public function test_managers_are_notified_of_an_escalation(): void
    {
        $manager = $this->manager();

        Ticket::factory()->priority(TicketPriority::Low)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(80),
        ]);

        app(EscalateOverdueTickets::class)->handle();

        Notification::assertSentTo($manager, TicketEscalatedNotification::class);
    }

    public function test_the_query_count_does_not_grow_with_the_number_of_tickets(): void
    {
        Ticket::factory()->count(3)->priority(TicketPriority::Low)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(80),
        ]);

        $few = $this->countDetectionQueries();

        Ticket::factory()->count(25)->priority(TicketPriority::Low)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(80),
        ]);

        $many = $this->countDetectionQueries();

        $this->assertSame($few, $many, "La detection passe de {$few} a {$many} requetes.");
        $this->assertSame(count(TicketPriority::cases()), $many);
    }

    public function test_the_dry_run_reports_without_writing(): void
    {
        $ticket = Ticket::factory()->priority(TicketPriority::Low)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(80),
        ]);

        $report = app(EscalateOverdueTickets::class)->preview();

        $this->assertSame(1, $report->escalated->count());
        $this->assertSame(TicketPriority::Low, $ticket->refresh()->priority);
        $this->assertNull($ticket->escalated_at);
    }

    public function test_the_command_runs_and_reports(): void
    {
        Ticket::factory()->priority(TicketPriority::Low)->create([
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(80),
        ]);

        $this->artisan('tickets:escalate')->assertSuccessful();
    }

    public function test_the_command_is_scheduled(): void
    {
        $scheduled = collect(app(Schedule::class)->events())
            ->contains(fn ($event): bool => str_contains($event->command ?? '', 'tickets:escalate'));

        $this->assertTrue($scheduled, 'La commande tickets:escalate n est pas planifiee.');
    }

    private function countDetectionQueries(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        app(EscalateOverdueTickets::class)->preview();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
