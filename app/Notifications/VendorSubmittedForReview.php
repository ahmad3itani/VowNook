<?php

namespace App\Notifications;

use App\Models\VendorProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells admins a vendor submitted their listing for moderation. */
class VendorSubmittedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public VendorProfile $vendor) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Vendor submitted for review — {$this->vendor->business_name}")
            ->line("{$this->vendor->business_name} submitted their listing and is awaiting approval.")
            ->action('Review vendors', url('/admin/vendors'));
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Vendor awaiting review',
            'body' => $this->vendor->business_name,
            'url' => '/admin/vendors',
            'icon' => 'store',
        ];
    }
}
