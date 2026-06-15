<?php

namespace App\Notifications;

use App\Models\Inquiry;
use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the couple's user when a vendor sends them an offer. */
class NewOfferReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Inquiry $inquiry,
        protected Offer $offer,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New quote received',
            'body' => ($this->inquiry->vendorProfile?->business_name ?? 'A vendor').' sent you a quote.',
            'url' => "/vendors/quotes/{$this->inquiry->id}",
            'icon' => 'file-text',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vendorName = $this->inquiry->vendorProfile?->business_name ?? 'A vendor';
        $total = '$'.number_format($this->offer->total_cents / 100, 2).' CAD';

        return (new MailMessage)
            ->subject('You received a quote from '.$vendorName)
            ->greeting('Good news!')
            ->line($vendorName.' has sent you a quote for '.$total.'.')
            ->action('Review Quote', url("/vendors/quotes/{$this->inquiry->id}"))
            ->line('You can accept, decline, or message the vendor with questions.');
    }
}
