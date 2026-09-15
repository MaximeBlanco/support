<?php

namespace App\Livewire\Tickets;

use App\Actions\Tickets\AddComment;
use App\Actions\Tickets\AssignTicket;
use App\Actions\Tickets\CloseTicket;
use App\Actions\Tickets\ReopenTicket;
use App\Actions\Tickets\ResolveTicket;
use App\Actions\Tickets\StartTicketWork;
use App\Actions\Tickets\UnassignTicket;
use App\Enums\RoleName;
use App\Exceptions\IllegalTicketTransition;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    use AuthorizesRequests;

    public Ticket $ticket;

    public ?int $assigneeId = null;

    #[Validate('required|string|min:2|max:2000')]
    public string $body = '';

    public function mount(Ticket $ticket): void
    {
        $this->authorize('view', $ticket);

        $this->ticket = $ticket;
        $this->assigneeId = $ticket->assignee_id;
    }

    public function assign(): void
    {
        $this->authorize('assign', $this->ticket);

        if ($this->assigneeId === null) {
            $this->addError('assigneeId', __('ticket.errors.no_technician'));

            return;
        }

        $technician = User::findOrFail($this->assigneeId);

        $this->runTransition(
            fn () => app(AssignTicket::class)->handle($this->ticket, $technician, auth()->user()),
            __('ticket.messages.assigned', ['name' => $technician->name]),
        );
    }

    public function assignToMe(): void
    {
        $this->assigneeId = auth()->id();
        $this->assign();
    }

    public function unassign(): void
    {
        $this->authorize('assign', $this->ticket);

        $this->runTransition(
            fn () => app(UnassignTicket::class)->handle($this->ticket, auth()->user()),
            __('ticket.messages.unassigned'),
        );

        $this->assigneeId = null;
    }

    public function start(): void
    {
        $this->authorize('transition', $this->ticket);

        $this->runTransition(
            fn () => app(StartTicketWork::class)->handle($this->ticket, auth()->user()),
            __('ticket.messages.started'),
        );
    }

    public function resolve(): void
    {
        $this->authorize('transition', $this->ticket);

        $this->runTransition(
            fn () => app(ResolveTicket::class)->handle($this->ticket, auth()->user()),
            __('ticket.messages.resolved'),
        );
    }

    public function reopen(): void
    {
        $this->authorize('transition', $this->ticket);

        $this->runTransition(
            fn () => app(ReopenTicket::class)->handle($this->ticket, auth()->user()),
            __('ticket.messages.reopened'),
        );
    }

    public function close(): void
    {
        $this->authorize('close', $this->ticket);

        $this->runTransition(
            fn () => app(CloseTicket::class)->handle($this->ticket, auth()->user()),
            __('ticket.messages.closed'),
        );
    }

    public function comment(): void
    {
        $this->authorize('comment', $this->ticket);
        $this->validateOnly('body');

        app(AddComment::class)->handle($this->ticket, auth()->user(), $this->body);

        $this->reset('body');
        $this->notify(__('ticket.messages.commented'));
    }

    private function runTransition(callable $operation, string $message): void
    {
        try {
            $operation();
        } catch (IllegalTicketTransition $exception) {
            $this->notify($exception->translatedMessage(), 'error');

            return;
        }

        $this->ticket->refresh();
        $this->notify($message);
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }

    /**
     * @return Collection<int, User>
     */
    private function technicians(): Collection
    {
        return User::query()
            ->select(['id', 'name'])
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::Technician))
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        $this->ticket->load([
            'requester:id,name,email',
            'assignee:id,name,email',
            'comments' => fn ($query) => $query->with('author:id,name')->latest(),
            'statusChanges' => fn ($query) => $query->with('author:id,name')->oldest(),
        ]);

        return view('livewire.tickets.show', [
            'technicians' => $this->technicians(),
        ]);
    }
}
