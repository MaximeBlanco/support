<?php

namespace App\Notifications;

use App\Imports\ImportReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketImportFinished extends Notification implements ShouldQueue
{
    use Queueable;

    private const REJECTIONS_IN_MAIL = 20;

    public function __construct(public readonly ImportReport $report) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('import.notification.subject'))
            ->greeting(__('notification.greeting', ['name' => $notifiable->name]))
            ->line(trans_choice('console.import.read', $this->report->read, ['count' => $this->report->read]))
            ->line(trans_choice('console.import.created', $this->report->created, ['count' => $this->report->created]))
            ->line(trans_choice('console.import.rejected', $this->report->rejectedCount(), ['count' => $this->report->rejectedCount()]));

        foreach (array_slice($this->report->rejections, 0, self::REJECTIONS_IN_MAIL) as $rejection) {
            $mail->line(__('import.notification.rejection', $rejection));
        }

        return $mail->action(__('import.notification.action'), route('tickets.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'import.notification.inbox',
            'reference' => (string) $this->report->created,
            'title' => __('import.notification.subject'),
            'report' => $this->report->toArray(),
        ];
    }
}
