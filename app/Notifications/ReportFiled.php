<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells admins a listing or review was reported. Transactional (moderation). */
class ReportFiled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Report $report) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A '.class_basename($this->report->reportable_type).' was reported')
            ->line('Reason: '.$this->report->reason->label())
            ->line($this->report->details ?: '')
            ->action('Review reports', url('/admin/reports'));
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New content report',
            'body' => $this->report->reason->label(),
            'url' => '/admin/reports',
            'icon' => 'flag',
        ];
    }
}
