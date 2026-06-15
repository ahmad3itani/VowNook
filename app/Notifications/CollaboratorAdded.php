<?php

namespace App\Notifications;

use App\Models\Wedding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to a user when they are added as a collaborator on a wedding. */
class CollaboratorAdded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Wedding $wedding,
        protected string $role,
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
            'title' => 'Added to a wedding',
            'body' => 'You can now help plan "'.$this->wedding->name.'".',
            'url' => '/dashboard',
            'icon' => 'users',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been invited to help plan '.$this->wedding->name)
            ->greeting('You are in!')
            ->line('You have been added to "'.$this->wedding->name.'" as a '.str_replace('_', ' ', $this->role).'.')
            ->action('Open Dashboard', url('/dashboard'))
            ->line('Switch to this wedding from your dashboard to start collaborating.');
    }
}
