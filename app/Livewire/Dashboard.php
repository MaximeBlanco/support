<?php

namespace App\Livewire;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    /**
     * @return BaseCollection<string, int>
     */
    private function countsBy(string $column): BaseCollection
    {
        return Ticket::query()
            ->visibleTo(auth()->user())
            ->selectRaw($column.', count(*) as aggregate')
            ->groupBy($column)
            ->pluck('aggregate', $column);
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function recentTickets(): Collection
    {
        return Ticket::query()
            ->visibleTo(auth()->user())
            ->with(['requester:id,name', 'assignee:id,name'])
            ->withCount('comments')
            ->latest()
            ->take(6)
            ->get();
    }

    public function render(): View
    {
        $byStatus = $this->countsBy('status');
        $byPriority = $this->countsBy('priority');

        $unassigned = Ticket::query()
            ->visibleTo(auth()->user())
            ->whereStillOpen()
            ->whereNull('assignee_id')
            ->count();

        $resolvedThisWeek = Ticket::query()
            ->visibleTo(auth()->user())
            ->where('resolved_at', '>=', now()->startOfWeek())
            ->count();

        return view('livewire.dashboard', [
            'stats' => [
                [
                    'label' => __('app.dashboard.open'),
                    'value' => (int) $byStatus->get(TicketStatus::Open->value, 0),
                    'icon' => 'inbox',
                    'tone' => 'text-sky-600 bg-sky-50 dark:bg-sky-400/10 dark:text-sky-300',
                ],
                [
                    'label' => __('app.dashboard.in_progress'),
                    'value' => (int) $byStatus->get(TicketStatus::InProgress->value, 0),
                    'icon' => 'clock',
                    'tone' => 'text-amber-600 bg-amber-50 dark:bg-amber-400/10 dark:text-amber-300',
                ],
                [
                    'label' => __('app.dashboard.resolved_week'),
                    'value' => $resolvedThisWeek,
                    'icon' => 'check-circle',
                    'tone' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-400/10 dark:text-emerald-300',
                ],
                [
                    'label' => __('app.dashboard.unassigned'),
                    'value' => $unassigned,
                    'icon' => 'users',
                    'tone' => 'text-violet-600 bg-violet-50 dark:bg-violet-400/10 dark:text-violet-300',
                ],
            ],
            'byStatus' => collect(TicketStatus::cases())->map(fn (TicketStatus $case): array => [
                'case' => $case,
                'total' => (int) $byStatus->get($case->value, 0),
            ]),
            'byPriority' => collect(TicketPriority::cases())->map(fn (TicketPriority $case): array => [
                'case' => $case,
                'total' => (int) $byPriority->get($case->value, 0),
            ]),
            'total' => (int) $byStatus->sum(),
            'recent' => $this->recentTickets(),
        ]);
    }
}
