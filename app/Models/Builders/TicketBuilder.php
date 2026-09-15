<?php

namespace App\Models\Builders;

use App\Enums\Permission;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Ticket>
 */
class TicketBuilder extends Builder
{
    /**
     * @var array<int, string>
     */
    public const SORTABLE_COLUMNS = [
        'reference',
        'title',
        'status',
        'priority',
        'created_at',
        'updated_at',
    ];

    public function visibleTo(User $user): self
    {
        if ($user->hasPermission(Permission::ViewAllTickets)) {
            return $this;
        }

        $canSeeOwn = $user->hasPermission(Permission::ViewOwnTickets);
        $canSeeAssigned = $user->hasPermission(Permission::ViewAssignedTickets);

        if (! $canSeeOwn && ! $canSeeAssigned) {
            return $this->whereRaw('1 = 0');
        }

        return $this->where(function (self $query) use ($user, $canSeeOwn, $canSeeAssigned): void {
            if ($canSeeOwn) {
                $query->orWhere('requester_id', $user->getKey());
            }

            if ($canSeeAssigned) {
                $query->orWhere('assignee_id', $user->getKey());
            }
        });
    }

    public function whereStatus(?TicketStatus $status): self
    {
        return $this->when($status, fn (self $query): self => $query->where('status', $status));
    }

    public function whereStillOpen(): self
    {
        return $this->whereIn('status', TicketStatus::openStates());
    }

    public function search(?string $term): self
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $this;
        }

        return $this->where(function (self $query) use ($term): void {
            $query->where('title', 'like', '%'.$term.'%')
                ->orWhere('reference', 'like', '%'.$term.'%')
                ->orWhere('description', 'like', '%'.$term.'%');
        });
    }

    public function sortBy(string $column, string $direction): self
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            $column = 'created_at';
        }

        return $this->orderBy($column, $direction === 'asc' ? 'asc' : 'desc');
    }
}
