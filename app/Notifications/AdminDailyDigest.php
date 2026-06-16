<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A once-a-day summary of platform activity, emailed to admins. */
class AdminDailyDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param array<string,int> $stats */
    public function __construct(public array $stats) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->stats;
        $gmv = '$'.number_format(($s['gmv_cents'] ?? 0) / 100, 2);

        return (new MailMessage)
            ->subject('VowNook — your daily admin digest')
            ->line('Activity on VowNook in the last 24 hours:')
            ->line("• New signups: {$s['new_users']}")
            ->line("• New weddings: {$s['new_weddings']}")
            ->line("• Vendors awaiting review: {$s['vendors_pending']}")
            ->line("• New bookings: {$s['new_bookings']} ({$gmv})")
            ->line("• Open reports: {$s['open_reports']}")
            ->action('Open the admin console', url('/admin'));
    }
}
