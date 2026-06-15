<?php

namespace App\Notifications;

use App\Models\WeddingInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emailed to an invitee (who may not have an account yet) with a secure link to
 * join a wedding. Sent on-demand: Notification::route('mail', $email)->notify(...).
 */
class WeddingInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public WeddingInvitation $invitation,
        public string $inviterName,
        public string $roleLabel,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $wedding = $this->invitation->wedding->name;
        $url = url('/invitations/'.$this->invitation->token);

        return (new MailMessage)
            ->subject("{$this->inviterName} invited you to help plan {$wedding}")
            ->greeting('You\'re invited!')
            ->line("{$this->inviterName} would like you to join \"{$wedding}\" as a {$this->roleLabel}.")
            ->line('Accept the invitation to start collaborating. If you don\'t have an account yet, you can create one in a moment.')
            ->action('Accept invitation', $url)
            ->line('This invitation expires in 14 days. If you weren\'t expecting it, you can safely ignore this email.');
    }
}
