<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Ticket $ticket) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notification.ticket_assigned.subject', ['reference' => $this->ticket->reference]))
            ->greeting(__('notification.greeting', ['name' => $notifiable->name]))
            ->line(__('notification.ticket_assigned.line', [
                'reference' => $this->ticket->reference,
                'title' => $this->ticket->title,
            ]))
            ->line(__('notification.ticket_assigned.priority', [
                'priority' => $this->ticket->priority->label(),
                'hours' => $this->ticket->priority->targetResolutionHours(),
            ]))
            ->action(__('notification.ticket_assigned.action'), route('tickets.show', $this->ticket));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->getKey(),
            'reference' => $this->ticket->reference,
            'title' => $this->ticket->title,
            'message_key' => 'notification.ticket_assigned.inbox',
        ];
    }
}
