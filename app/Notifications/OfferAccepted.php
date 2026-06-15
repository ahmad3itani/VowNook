<?php

namespace App\Notifications;

use App\Models\Inquiry;
use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the vendor's user when the couple accepts their offer. */
class OfferAccepted extends Notification implements ShouldQueue
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
            'title' => 'Your offer was accepted',
            'body' => ($this->inquiry->wedding?->name ?? 'A couple').' accepted your offer.',
            'url' => "/vendor/inquiries/{$this->inquiry->id}",
            'icon' => 'party-popper',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $weddingName = $this->inquiry->wedding?->name ?? 'The couple';
        $total = '$'.number_format($this->offer->total_cents / 100, 2).' CAD';

        return (new MailMessage)
            ->subject('Your offer was accepted!')
            ->greeting('Congratulations!')
            ->line($weddingName.' has accepted your offer of '.$total.'.')
            ->action('View Booking', url("/vendor/inquiries/{$this->inquiry->id}"))
            ->line('The booking is now pending payment.');
    }
}
