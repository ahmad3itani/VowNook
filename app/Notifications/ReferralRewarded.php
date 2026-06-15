<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells a referrer they earned a free month of Premium. */
class ReferralRewarded extends Notification implements ShouldQueue
{
    use Queueable, RespectsPreferences;

    public function __construct(public string $friendName, public int $days) {}

    protected function marketingCategory(): ?string
    {
        return 'product_updates';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('You earned '.$this->days.' days of Premium 🎉')
            ->greeting('Thank you for spreading the word!')
            ->line($this->friendName.' joined and started planning thanks to you.')
            ->line('We’ve added '.$this->days.' days of Premium to your account.')
            ->action('See your plan', url('/settings/plan'));

        return $this->withCaslFooter($mail, $notifiable);
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'You earned '.$this->days.' days of Premium',
            'body' => $this->friendName.' joined thanks to your referral.',
            'url' => '/settings/plan',
            'icon' => 'gift',
        ];
    }
}
