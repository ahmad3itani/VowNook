<?php

namespace App\Notifications;

use App\Models\Guest;
use App\Models\Wedding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A couple's announcement to a guest — sent on the couple's behalf to the
 * guest's email (guests aren't platform users). Body is plain text the couple
 * wrote; each paragraph becomes a line.
 */
class GuestBroadcastMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Wedding $wedding,
        public Guest $guest,
        public string $subjectLine,
        public string $body,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subjectLine)
            ->greeting('Hi '.$this->guest->first_name.',');

        // Each non-empty paragraph (split on blank lines) becomes a mail line.
        foreach (preg_split('/\n\s*\n/', trim($this->body)) as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph !== '') {
                $mail->line($paragraph);
            }
        }

        return $mail
            ->action('Visit our wedding site', url('/w/'.$this->wedding->slug))
            ->salutation('With love, '.$this->wedding->name);
    }
}
