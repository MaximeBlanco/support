<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketNotificationPolicies;
use Illuminate\Support\Facades\Notification;

class NotifyAssignedTechnician
{
    public function __construct(private readonly TicketNotificationPolicies $policies) {}

    public function handle(TicketAssigned $event): void
    {
        $notification = new TicketAssignedNotification($event->ticket);

        $event->technician->notify($notification);

        $escalated = $this->policies
            ->for($event->ticket->priority)
            ->additionalRecipients($event->ticket);

        Notification::send($escalated, $notification);
    }
}
