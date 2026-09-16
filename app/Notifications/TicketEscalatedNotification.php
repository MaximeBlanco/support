<?php

namespace App\Notifications;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketPriority $from,
        public readonly ?TicketPriority $to,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $key = $this->to === null ? 'capped' : 'raised';

        return (new MailMessage)
            ->subject(__('notification.ticket_escalated.subject', ['reference' => $this->ticket->reference]))
            ->greeting(__('notification.greeting', ['name' => $notifiable->name]))
            ->line(__('notification.ticket_escalated.'.$key, [
                'reference' => $this->ticket->reference,
                'title' => $this->ticket->title,
                'from' => $this->from->label(),
                'to' => $this->to?->label() ?? $this->from->label(),
                'hours' => $this->from->targetResolutionHours(),
            ]))
            ->action(__('notification.ticket_escalated.action'), route('tickets.show', $this->ticket));
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
            'message_key' => 'notification.ticket_escalated.inbox',
        ];
    }
}
