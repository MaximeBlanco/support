<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Livewire\Tickets\Form;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketFormTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
    }

    public function test_a_requester_creates_a_ticket(): void
    {
        $requester = $this->requester();

        Livewire::actingAs($requester)
            ->test(Form::class)
            ->set('title', 'Imprimante du 2e étage hors service')
            ->set('description', 'Elle affiche un bourrage papier alors qu’il n’y en a pas.')
            ->set('priority', TicketPriority::High->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'requester_id' => $requester->getKey(),
            'title' => 'Imprimante du 2e étage hors service',
            'priority' => TicketPriority::High->value,
            'status' => TicketStatus::Open->value,
        ]);
    }

    public function test_it_refuses_an_empty_form(): void
    {
        Livewire::actingAs($this->requester())
            ->test(Form::class)
            ->set('title', '')
            ->set('description', '')
            ->call('save')
            ->assertHasErrors(['title' => 'required', 'description' => 'required']);
    }

    public function test_it_refuses_a_title_that_is_too_short(): void
    {
        Livewire::actingAs($this->requester())
            ->test(Form::class)
            ->set('title', 'bug')
            ->set('description', 'Une description suffisamment longue pour passer.')
            ->call('save')
            ->assertHasErrors(['title' => 'min']);
    }

    public function test_the_priority_is_validated_against_the_enum(): void
    {
        Livewire::actingAs($this->requester())
            ->test(Form::class)
            ->set('title', 'Un objet parfaitement valable')
            ->set('description', 'Une description suffisamment longue pour passer.')
            ->set('priority', 'urgentissime')
            ->call('save')
            ->assertHasErrors('priority');
    }

    public function test_validation_messages_are_translated(): void
    {
        $component = Livewire::actingAs($this->requester())
            ->test(Form::class)
            ->set('title', '')
            ->set('description', '')
            ->call('save');

        $this->assertStringContainsString('obligatoire', $component->errors()->first('title'));
    }

    public function test_a_ticket_can_be_edited_by_its_requester(): void
    {
        $requester = $this->requester();
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->getKey(),
            'status' => TicketStatus::Open,
        ]);

        Livewire::actingAs($requester)
            ->test(Form::class, ['ticket' => $ticket])
            ->assertSet('title', $ticket->title)
            ->set('title', 'Objet corrigé après relecture')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Objet corrigé après relecture', $ticket->refresh()->title);
    }

    public function test_a_stranger_cannot_edit_someone_elses_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($this->requester())
            ->test(Form::class, ['ticket' => $ticket])
            ->assertForbidden();
    }

    public function test_a_technician_cannot_open_the_creation_form(): void
    {
        Livewire::actingAs($this->technician())
            ->test(Form::class)
            ->assertForbidden();
    }
}
