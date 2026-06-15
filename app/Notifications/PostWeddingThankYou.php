<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A warm note after the wedding, with a thank-you perk code. */
class PostWeddingThankYou extends Notification implements ShouldQueue
{
    use Queueable, RespectsPreferences;

    public function __construct(
        public string $weddingName,
        public ?string $perkCode,
        public int $perkDays,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Congratulations on your wedding! 🥂')
            ->greeting('Congratulations!')
            ->line('We hope '.$this->weddingName.' was everything you dreamed of. Thank you for planning it with us.');

        if ($this->perkCode) {
            $mail->line('As a thank-you, here’s '.$this->perkDays.' days of Premium on us — redeem this code in your plan settings:')
                ->line('**'.$this->perkCode.'**')
                ->action('Redeem your perk', url('/settings/plan'));
        }

        $mail->line('If you have a moment, leaving a review for your vendors helps other couples enormously.');

        return $this->withCaslFooter($mail, $notifiable);
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Congratulations on your wedding!',
            'body' => $this->perkCode ? 'Enjoy a thank-you gift of Premium access.' : 'Thank you for planning with us.',
            'url' => '/settings/plan',
            'icon' => 'party-popper',
        ];
    }
}
