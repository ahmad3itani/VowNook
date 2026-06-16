<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells admins a new account just signed up. Oversight (always delivered). */
class NewUserRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type = ucfirst($this->user->account_type?->value ?? 'couple');

        return (new MailMessage)
            ->subject("New {$type} signed up — {$this->user->name}")
            ->line("{$this->user->name} ({$this->user->email}) just created a {$type} account on VowNook.")
            ->action('View users', url('/admin/users'));
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        $type = $this->user->account_type?->value ?? 'couple';

        return [
            'title' => 'New '.$type.' signup',
            'body' => $this->user->name.' · '.$this->user->email,
            'url' => '/admin/users',
            'icon' => 'user-plus',
        ];
    }
}
