<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts admins the moment a cardholder disputes a booking charge, so someone
 * can submit evidence in Stripe before the response deadline — the difference
 * between winning the dispute and the platform (and vendor) eating the loss.
 */
class PaymentDisputeOpened extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking, public int $amountCents) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠ Payment dispute opened on VowNook')
            ->line("A charge for {$this->weddingName()} ({$this->vendorName()}) was disputed.")
            ->line('Disputed amount: '.$this->amount())
            ->line('Respond in the Stripe Dashboard before the deadline to contest it.')
            ->action('View marketplace', url('/admin/marketplace'));
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Payment dispute opened',
            'body' => $this->vendorName().' · '.$this->weddingName().' · '.$this->amount().' disputed',
            'url' => '/admin/marketplace',
            'icon' => 'alert-triangle',
        ];
    }

    private function vendorName(): string
    {
        return $this->booking->vendorProfile?->business_name ?? 'A vendor';
    }

    private function weddingName(): string
    {
        return $this->booking->wedding?->name ?? 'a wedding';
    }

    private function amount(): string
    {
        return '$'.number_format($this->amountCents / 100, 2);
    }
}
