<?php

namespace App\Notifications\Policies;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketNotificationPolicy;

class StandardPolicy implements TicketNotificationPolicy
{
    public function handles(TicketPriority $priority): bool
    {
        return $priority === TicketPriority::Low || $priority === TicketPriority::Normal;
    }

    /**
     * @return array<int, string>
     */
    public function channels(): array
    {
        return ['mail', 'database'];
    }

    /**
     * @return array<int, User>
     */
    public function additionalRecipients(Ticket $ticket): array
    {
        return [];
    }
}
