<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reminds a vendor about inquiries they haven't replied to. Transactional
 * (protects their lead response time / the "responds in ~Xh" badge).
 */
class VendorUnansweredInquiries extends Notification implements ShouldQueue
{
    use Queueable, RespectsPreferences;

    public function __construct(public int $count) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('You have '.$this->count.' inquiry'.($this->count === 1 ? '' : ' inquiries').' waiting for a reply')
            ->greeting('Don’t keep couples waiting')
            ->line('Fast responses win bookings — couples are far more likely to book the vendor who replies first.')
            ->line('You have '.$this->count.' unanswered inquiry'.($this->count === 1 ? '' : ' inquiries').'.')
            ->action('Reply now', url('/vendor/inquiries'));

        return $this->withCaslFooter($mail, $notifiable);
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->count.' inquiry'.($this->count === 1 ? '' : ' inquiries').' awaiting reply',
            'body' => 'Reply quickly to win more bookings.',
            'url' => '/vendor/inquiries',
            'icon' => 'message-circle',
        ];
    }
}
