<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Stands in for the on-call paging system.
 *
 * A structured log line is enough here: what matters is that the urgent path
 * exists and is chosen by the policy, not which vendor eventually receives it.
 */
class UrgencyChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toUrgency')) {
            return;
        }

        Log::channel('urgency')->warning('ticket.urgent', [
            'recipient' => $notifiable->getKey(),
            ...$notification->toUrgency($notifiable),
        ]);
    }
}
