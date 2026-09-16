<?php

namespace App\Notifications;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\User;

/**
 * How loudly a ticket of a given priority is announced.
 *
 * One implementation per level of urgency, dropped in the Policies directory.
 * Nothing maps priorities to policies from the outside: each policy says which
 * priorities it answers for, so a new level of urgency is a new file and no edit.
 */
interface TicketNotificationPolicy
{
    public function handles(TicketPriority $priority): bool;

    /**
     * @return array<int, string>
     */
    public function channels(): array;

    /**
     * People warned on top of the ticket's own recipient.
     *
     * @return array<int, User>
     */
    public function additionalRecipients(Ticket $ticket): array;
}
