<?php

namespace App\Notifications;

use App\Models\Guest;
use App\Models\Wedding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the wedding owner when a guest responds via the public RSVP page. */
class RsvpReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Wedding $wedding,
        protected Guest $guest,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $guestName = trim($this->guest->first_name.' '.($this->guest->last_name ?? ''));
        $status = $this->guest->rsvp_status?->value ?? 'responded';

        return (new MailMessage)
            ->subject('New RSVP for '.$this->wedding->name)
            ->greeting('You have a new RSVP!')
            ->line($guestName.' has responded: '.str_replace('_', ' ', $status).'.')
            ->action('View Guest List', url('/guests'))
            ->line('Keep an eye on your guest list as more replies come in.');
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        $guestName = trim($this->guest->first_name.' '.($this->guest->last_name ?? ''));

        return [
            'title' => 'New RSVP received',
            'body' => $guestName.' responded: '.str_replace('_', ' ', $this->guest->rsvp_status?->value ?? 'responded'),
            'url' => '/guests',
            'icon' => 'mail-open',
        ];
    }
}
