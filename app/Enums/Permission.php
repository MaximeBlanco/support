<?php

namespace App\Enums;

enum Permission: string
{
    case ViewOwnTickets = 'tickets.view_own';
    case ViewAssignedTickets = 'tickets.view_assigned';
    case ViewAllTickets = 'tickets.view_all';
    case CreateTicket = 'tickets.create';
    case CommentTicket = 'tickets.comment';
    case AssignTicket = 'tickets.assign';
    case TransitionTicket = 'tickets.transition';
    case CloseTicket = 'tickets.close';

    public function label(): string
    {
        return __('permission.'.$this->value);
    }
}
