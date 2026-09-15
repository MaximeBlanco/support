<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Livewire\Tickets\Show;
use App\Models\Ticket;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketActionsTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Notification::fake();
    }

    public function test_a_manager_assigns_a_ticket_from_the_interface(): void
    {
        $manager = $this->manager();
        $technician = $this->technician();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        Livewire::actingAs($manager)
            ->test(Show::class, ['ticket' => $ticket])
            ->set('assigneeId', $technician->getKey())
            ->call('assign')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $ticket->refresh();

        $this->assertSame(TicketStatus::Assigned, $ticket->status);
        $this->assertSame($technician->getKey(), $ticket->assignee_id);

        Notification::assertSentToTimes($technician, TicketAssignedNotification::class, 1);
    }

    public function test_assigning_without_a_technician_shows_a_field_error(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        Livewire::actingAs($this->manager())
            ->test(Show::class, ['ticket' => $ticket])
            ->set('assigneeId', null)
            ->call('assign')
            ->assertHasErrors('assigneeId');
    }

    public function test_an_illegal_transition_shows_a_message_instead_of_crashing(): void
    {
        $technician = $this->technician();
        $ticket = Ticket::factory()->assignedTo($technician)->create();

        Livewire::actingAs($technician)
            ->test(Show::class, ['ticket' => $ticket])
            ->call('resolve')
            ->assertOk()
            ->assertDispatched('notify', type: 'error');

        $this->assertSame(TicketStatus::Assigned, $ticket->refresh()->status);
    }

    public function test_a_technician_walks_their_ticket_through_the_lifecycle(): void
    {
        $technician = $this->technician();
        $ticket = Ticket::factory()->assignedTo($technician)->create();

        Livewire::actingAs($technician)
            ->test(Show::class, ['ticket' => $ticket])
            ->call('start')
            ->assertDispatched('notify');

        $this->assertSame(TicketStatus::InProgress, $ticket->refresh()->status);
    }

    public function test_a_requester_cannot_transition_a_ticket(): void
    {
        $requester = $this->requester();
        $ticket = Ticket::factory()->assignedTo($this->technician())->create([
            'requester_id' => $requester->getKey(),
        ]);

        Livewire::actingAs($requester)
            ->test(Show::class, ['ticket' => $ticket])
            ->call('start')
            ->assertForbidden();
    }

    public function test_a_comment_is_published_and_the_field_is_cleared(): void
    {
        $requester = $this->requester();
        $ticket = Ticket::factory()->create(['requester_id' => $requester->getKey()]);

        Livewire::actingAs($requester)
            ->test(Show::class, ['ticket' => $ticket])
            ->set('body', 'Le problème est réapparu ce matin.')
            ->call('comment')
            ->assertHasNoErrors()
            ->assertSet('body', '');

        $this->assertDatabaseHas('comments', [
            'ticket_id' => $ticket->getKey(),
            'author_id' => $requester->getKey(),
            'body' => 'Le problème est réapparu ce matin.',
        ]);
    }

    public function test_an_empty_comment_is_refused(): void
    {
        $requester = $this->requester();
        $ticket = Ticket::factory()->create(['requester_id' => $requester->getKey()]);

        Livewire::actingAs($requester)
            ->test(Show::class, ['ticket' => $ticket])
            ->set('body', '')
            ->call('comment')
            ->assertHasErrors('body');
    }

    public function test_only_a_manager_closes_a_resolved_ticket(): void
    {
        $technician = $this->technician();
        $ticket = Ticket::factory()->resolved($technician)->create();

        Livewire::actingAs($this->manager())
            ->test(Show::class, ['ticket' => $ticket])
            ->call('close')
            ->assertHasNoErrors();

        $this->assertSame(TicketStatus::Closed, $ticket->refresh()->status);
    }
}
