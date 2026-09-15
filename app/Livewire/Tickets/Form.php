<?php

namespace App\Livewire\Tickets;

use App\Actions\Tickets\CreateTicket;
use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Ticket $ticket = null;

    public string $title = '';

    public string $description = '';

    public string $priority = '';

    public function mount(?Ticket $ticket = null): void
    {
        $this->priority = TicketPriority::Normal->value;

        if ($ticket?->exists) {
            $this->authorize('update', $ticket);

            $this->ticket = $ticket;
            $this->title = $ticket->title;
            $this->description = $ticket->description;
            $this->priority = $ticket->priority->value;

            return;
        }

        $this->authorize('create', Ticket::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $priority = TicketPriority::from($this->priority);

        if ($this->ticket !== null) {
            $this->ticket->update([
                'title' => $this->title,
                'description' => $this->description,
                'priority' => $priority,
            ]);

            session()->flash('status', __('ticket.messages.updated'));
            $this->redirect(route('tickets.show', $this->ticket), navigate: true);

            return;
        }

        $ticket = app(CreateTicket::class)->handle(
            auth()->user(),
            $this->title,
            $this->description,
            $priority,
        );

        session()->flash('status', __('ticket.messages.created', ['reference' => $ticket->reference]));
        $this->redirect(route('tickets.show', $ticket), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.tickets.form', [
            'priorities' => TicketPriority::cases(),
        ]);
    }
}
