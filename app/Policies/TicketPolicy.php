<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewAllTickets)
            || $user->hasPermission(Permission::ViewOwnTickets)
            || $user->hasPermission(Permission::ViewAssignedTickets);
    }

    public function viewAll(User $user): bool
    {
        return $user->hasPermission(Permission::ViewAllTickets);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->hasPermission(Permission::ViewAllTickets)) {
            return true;
        }

        if ($user->hasPermission(Permission::ViewOwnTickets) && $ticket->requester_id === $user->getKey()) {
            return true;
        }

        return $user->hasPermission(Permission::ViewAssignedTickets)
            && $ticket->assignee_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::CreateTicket);
    }

    public function import(User $user): bool
    {
        return $user->hasPermission(Permission::CreateTicket)
            && $user->hasPermission(Permission::AssignTicket);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if (! $this->view($user, $ticket)) {
            return false;
        }

        if ($ticket->status->isSettled()) {
            return false;
        }

        return $user->hasPermission(Permission::AssignTicket)
            || $ticket->requester_id === $user->getKey();
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::CommentTicket)
            && $this->view($user, $ticket)
            && $ticket->status !== TicketStatus::Closed;
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::AssignTicket) && ! $ticket->status->isSettled();
    }

    public function transition(User $user, Ticket $ticket): bool
    {
        if (! $user->hasPermission(Permission::TransitionTicket)) {
            return false;
        }

        return $this->view($user, $ticket);
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::CloseTicket)
            && $ticket->status === TicketStatus::Resolved;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::ViewAllTickets)
            && $user->hasPermission(Permission::CloseTicket);
    }
}
