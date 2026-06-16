<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells admins a couple accepted an offer and a booking was created. */
class NewBookingPlaced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New booking placed on VowNook')
            ->line("{$this->vendorName()} was booked for {$this->weddingName()}.")
            ->line('Total: '.$this->total())
            ->action('View marketplace', url('/admin/marketplace'));
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New booking placed',
            'body' => $this->vendorName().' · '.$this->weddingName().' · '.$this->total(),
            'url' => '/admin/marketplace',
            'icon' => 'calendar-check',
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

    private function total(): string
    {
        return '$'.number_format($this->booking->total_cents / 100, 2);
    }
}
