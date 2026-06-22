<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the requester when a staff member replies to their ticket. */
class SupportTicketReplied extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public string $body,
    ) {}

    public function via(object $notifiable): array
    {
        // Database first so the in-app notice survives a mail transport failure.
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Re: {$this->ticket->subject}")
            ->greeting("Hi {$this->ticket->name},")
            ->line('Our team replied to your support request:')
            ->line($this->body);

        // In-app users can view the thread; guests reply to the email.
        if ($this->ticket->user_id) {
            $mail->action('View conversation', route('support.show', $this->ticket));
        } else {
            $mail->line('Reply to this email to continue the conversation.');
        }

        return $mail->salutation('— '.config('app.name').' Support');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Support replied',
            'body' => $this->ticket->subject,
            'url' => $this->ticket->user_id ? route('support.show', $this->ticket) : null,
            'icon' => 'life-buoy',
        ];
    }
}
