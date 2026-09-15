<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Notifications\TicketAssignedNotification;

class NotifyAssignedTechnician
{
    public function handle(TicketAssigned $event): void
    {
        $event->technician->notify(new TicketAssignedNotification($event->ticket));
    }
}
