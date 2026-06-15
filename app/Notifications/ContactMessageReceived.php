<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to platform admins when someone submits the public contact form. */
class ContactMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $name,
        public string $email,
        public string $topic,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Contact form — {$this->topic} — {$this->name}")
            ->replyTo($this->email, $this->name)
            ->line("From: {$this->name} <{$this->email}>")
            ->line("Topic: {$this->topic}")
            ->line('Message:')
            ->line($this->message);
    }
}
