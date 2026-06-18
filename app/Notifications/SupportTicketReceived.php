<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to platform admins when a new support ticket is opened. */
class SupportTicketReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New support ticket — {$this->ticket->subject}")
            ->replyTo($this->ticket->email, $this->ticket->name)
            ->line("From: {$this->ticket->name} <{$this->ticket->email}>")
            ->line("Category: {$this->ticket->category}")
            ->line('Message:')
            ->line($this->ticket->message)
            ->action('Open in support inbox', route('admin.support.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New support ticket',
            'body' => $this->ticket->subject,
            'url' => route('admin.support.show', $this->ticket),
            'icon' => 'life-buoy',
        ];
    }
}
