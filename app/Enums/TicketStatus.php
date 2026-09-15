<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::Assigned],
            self::Assigned => [self::InProgress, self::Open],
            self::InProgress => [self::Resolved, self::Assigned],
            self::Resolved => [self::Closed, self::InProgress],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function isSettled(): bool
    {
        return $this === self::Resolved || $this === self::Closed;
    }

    public function label(): string
    {
        return __('ticket.status.'.$this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Open => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/30',
            self::Assigned => 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-400/10 dark:text-violet-300 dark:ring-violet-400/30',
            self::InProgress => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/30',
            self::Resolved => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/30',
            self::Closed => 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/30',
        };
    }

    public function dotClasses(): string
    {
        return match ($this) {
            self::Open => 'bg-sky-500',
            self::Assigned => 'bg-violet-500',
            self::InProgress => 'bg-amber-500',
            self::Resolved => 'bg-emerald-500',
            self::Closed => 'bg-slate-400',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function openStates(): array
    {
        return [self::Open, self::Assigned, self::InProgress];
    }
}
