<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketImportFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $reason) {}

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
            ->error()
            ->subject(__('import.notification.failed_subject'))
            ->greeting(__('notification.greeting', ['name' => $notifiable->name]))
            ->line(__('import.notification.failed_line'))
            ->line($this->reason);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'import.notification.failed_inbox',
            'reference' => '',
            'title' => __('import.notification.failed_subject'),
            'reason' => $this->reason,
        ];
    }
}
