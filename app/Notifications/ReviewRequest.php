<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Asks the couple to review the vendors they booked. */
class ReviewRequest extends Notification implements ShouldQueue
{
    use Queueable, RespectsPreferences;

    /** @param list<string> $vendorNames */
    public function __construct(public array $vendorNames) {}

    protected function marketingCategory(): ?string
    {
        return 'product_updates';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('How were your wedding vendors?')
            ->greeting('Share your experience')
            ->line('Your honest reviews help other couples find great vendors — and every review on our marketplace is tied to a real booking.');

        foreach ($this->vendorNames as $name) {
            $mail->line('• '.$name);
        }

        $mail->action('Leave a review', url('/vendors'));

        return $this->withCaslFooter($mail, $notifiable);
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Review your wedding vendors',
            'body' => 'Help other couples by sharing your experience.',
            'url' => '/vendors',
            'icon' => 'star',
        ];
    }
}
