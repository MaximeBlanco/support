<?php

namespace App\Notifications\Policies;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Channels\UrgencyChannel;
use App\Notifications\TicketNotificationPolicy;

class UrgentPolicy implements TicketNotificationPolicy
{
    public function handles(TicketPriority $priority): bool
    {
        return $priority === TicketPriority::High;
    }

    /**
     * @return array<int, string>
     */
    public function channels(): array
    {
        return ['mail', 'database', UrgencyChannel::class];
    }

    /**
     * @return array<int, User>
     */
    public function additionalRecipients(Ticket $ticket): array
    {
        return [];
    }
}
