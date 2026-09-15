<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';

    public function targetResolutionHours(): int
    {
        return match ($this) {
            self::Low => 72,
            self::Normal => 24,
            self::High => 8,
            self::Critical => 2,
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Normal => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::Low => self::Normal,
            self::Normal => self::High,
            self::High => self::Critical,
            self::Critical => null,
        };
    }

    public function label(): string
    {
        return __('ticket.priority.'.$this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/30',
            self::Normal => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/30',
            self::High => 'bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-400/10 dark:text-orange-300 dark:ring-orange-400/30',
            self::Critical => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/30',
        };
    }

    public function dotClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-400',
            self::Normal => 'bg-sky-500',
            self::High => 'bg-orange-500',
            self::Critical => 'bg-rose-500',
        };
    }
}
