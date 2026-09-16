<?php

namespace App\Listeners;

use App\Enums\Permission;
use App\Events\TicketEscalated;
use App\Models\User;
use App\Notifications\TicketEscalatedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyManagersOfEscalation
{
    public function handle(TicketEscalated $event): void
    {
        $managers = User::query()
            ->withPermission(Permission::AssignTicket)
            ->get();

        Notification::send(
            $managers,
            new TicketEscalatedNotification($event->ticket, $event->from, $event->to),
        );
    }
}
