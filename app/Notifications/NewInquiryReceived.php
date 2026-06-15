<?php

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Sent to the vendor's user when a couple submits a new inquiry. */
class NewInquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Inquiry $inquiry) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New inquiry received',
            'body' => ($this->inquiry->wedding?->name ?? 'A couple').' is interested in your services.',
            'url' => "/vendor/inquiries/{$this->inquiry->id}",
            'icon' => 'inbox',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $weddingName = $this->inquiry->wedding?->name ?? 'A couple';

        $mail = (new MailMessage)
            ->subject('New inquiry from '.$weddingName)
            ->greeting('You have a new inquiry!')
            ->line($weddingName.' has reached out about your services.');

        if ($this->inquiry->event_date) {
            $mail->line('Event date: '.$this->inquiry->event_date->toFormattedDateString());
        }

        return $mail
            ->line('"'.Str::limit($this->inquiry->message, 200).'"')
            ->action('View Inquiry', url("/vendor/inquiries/{$this->inquiry->id}"))
            ->line('Reply quickly to win the booking — couples love fast responses.');
    }
}
