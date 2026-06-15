<?php

namespace App\Notifications;

use App\Models\Guest;
use App\Models\Wedding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A friendly nudge to a guest who hasn't replied yet. Sent on the couple's
 * behalf to the guest's email (guests aren't platform users).
 */
class GuestRsvpReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Wedding $wedding,
        public Guest $guest,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = $this->wedding->event_date?->format('F j, Y');

        $mail = (new MailMessage)
            ->subject('Kindly RSVP — '.$this->wedding->name)
            ->greeting('Hi '.$this->guest->first_name.',')
            ->line($this->wedding->name.($date ? ' is on '.$date.'.' : '.'))
            ->line('We’d love to know if you can join us — it only takes a moment.')
            ->action('RSVP now', url('/w/'.$this->wedding->slug.'/rsvp'))
            ->salutation('With love, '.$this->wedding->name);

        return $mail;
    }
}
