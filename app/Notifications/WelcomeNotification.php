<?php

namespace App\Notifications;

use App\Enums\AccountType;
use App\Models\User;
use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once when a user registers. Role-specific next steps. Transactional
 * (account lifecycle), so it always sends — but still carries the CASL footer.
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsPreferences;

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Welcome to '.config('app.name'))
            ->greeting('Welcome, '.$notifiable->name.'!');

        match (true) {
            $notifiable instanceof User && $notifiable->account_type === AccountType::Vendor => $mail
                ->line('Thanks for joining as a vendor. Build your profile, add your services and photos, then submit it for review to start receiving inquiries.')
                ->action('Set up your vendor profile', url('/vendor')),
            $notifiable instanceof User && $notifiable->account_type === AccountType::Planner => $mail
                ->line('Your planner HQ is ready. Create your first client wedding, apply a checklist or budget template, and manage everything from one place.')
                ->action('Open your planner HQ', url('/planner')),
            default => $mail
                ->line('Your free wedding-planning studio is ready — guest list, budget, seating, timeline and a beautiful wedding website. When you’re ready, browse our vetted vendor marketplace too.')
                ->action('Start planning', url('/dashboard')),
        };

        $mail->line('We’re so glad you’re here.');

        return $this->withCaslFooter($mail, $notifiable);
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Welcome to '.config('app.name'),
            'body' => 'Your account is ready — let’s start planning.',
            'url' => '/dashboard',
            'icon' => 'sparkles',
        ];
    }
}
