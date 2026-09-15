<?php

namespace App\Livewire\Tickets;

use App\Enums\Permission;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Builders\TicketBuilder;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    private const PER_PAGE = 25;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $priority = '';

    #[Url(except: false)]
    public bool $onlyMine = false;

    #[Url(except: 'created_at')]
    public string $sortColumn = 'created_at';

    #[Url(except: 'desc')]
    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('viewAny', Ticket::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPriority(): void
    {
        $this->resetPage();
    }

    public function updatedOnlyMine(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, TicketBuilder::SORTABLE_COLUMNS, true)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'priority', 'onlyMine']);
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->status !== '' || $this->priority !== '' || $this->onlyMine;
    }

    /**
     * @return LengthAwarePaginator<int, Ticket>
     */
    private function tickets(): LengthAwarePaginator
    {
        $user = auth()->user();

        return Ticket::query()
            ->visibleTo($user)
            ->with(['requester:id,name', 'assignee:id,name'])
            ->withCount('comments')
            ->search($this->search)
            ->whereStatus(TicketStatus::tryFrom($this->status))
            ->when(
                TicketPriority::tryFrom($this->priority),
                fn (TicketBuilder $query, TicketPriority $priority): TicketBuilder => $query->where('priority', $priority),
            )
            ->when(
                $this->onlyMine && $user->hasPermission(Permission::ViewAllTickets),
                fn (TicketBuilder $query): TicketBuilder => $query->where(function (TicketBuilder $scoped) use ($user): void {
                    $scoped->where('requester_id', $user->getKey())
                        ->orWhere('assignee_id', $user->getKey());
                }),
            )
            ->sortBy($this->sortColumn, $this->sortDirection)
            ->paginate(self::PER_PAGE);
    }

    public function render(): View
    {
        return view('livewire.tickets.index', [
            'tickets' => $this->tickets(),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
        ]);
    }
}
