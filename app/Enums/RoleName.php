<?php

namespace App\Enums;

enum RoleName: string
{
    case Requester = 'requester';
    case Technician = 'technician';
    case Manager = 'manager';

    /**
     * @return array<int, Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Requester => [
                Permission::ViewOwnTickets,
                Permission::CreateTicket,
                Permission::CommentTicket,
            ],
            self::Technician => [
                Permission::ViewAssignedTickets,
                Permission::CommentTicket,
                Permission::TransitionTicket,
            ],
            self::Manager => [
                Permission::ViewAllTickets,
                Permission::CreateTicket,
                Permission::CommentTicket,
                Permission::AssignTicket,
                Permission::TransitionTicket,
                Permission::CloseTicket,
            ],
        };
    }

    public function label(): string
    {
        return __('role.'.$this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Requester => 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/30',
            self::Technician => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-400/10 dark:text-indigo-300 dark:ring-indigo-400/30',
            self::Manager => 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-600/20 dark:bg-fuchsia-400/10 dark:text-fuchsia-300 dark:ring-fuchsia-400/30',
        };
    }
}
