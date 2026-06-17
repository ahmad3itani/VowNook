<?php

namespace App\Notifications;

use App\Models\Guest;
use App\Models\Wedding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A branded save-the-date or invitation emailed to a guest on the couple's
 * behalf, with a 1x1 tracking pixel (markdown image) used to record opens.
 */
class GuestSaveTheDate extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Wedding $wedding,
        public Guest $guest,
        public string $kind,   // save_the_date|invitation
        public string $token,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isInvite = $this->kind === 'invitation';
        $date = $this->wedding->event_date?->translatedFormat('l, F j, Y');
        $pixel = url('/e/'.$this->token.'.gif');

        $mail = (new MailMessage)
            ->subject(($isInvite ? "You're invited — " : 'Save the Date — ').$this->wedding->name)
            ->greeting('Dear '.$this->guest->first_name.',')
            ->line($isInvite
                ? 'With joy, we invite you to celebrate the wedding of '.$this->wedding->name.'.'
                : 'Please save the date for the wedding of '.$this->wedding->name.'.')
            ->line($date ? '**'.$date.'**' : '');

        if ($isInvite) {
            $mail->action('RSVP now', url('/w/'.$this->wedding->slug.'/rsvp'));
        } else {
            $mail->action('View our website', url('/w/'.$this->wedding->slug));
        }

        $mail->line('A formal invitation with all the details will follow.')
            ->salutation('With love, '.$this->wedding->name)
            // 1x1 open-tracking pixel (markdown image → <img>).
            ->line('![]('.$pixel.')');

        return $mail;
    }
}
