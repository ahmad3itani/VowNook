<?php

namespace App\Notifications;

use App\Models\Wedding;
use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** "X days to go" countdown email at 100 / 30 / 7 / 1 days before the wedding. */
class WeddingMilestone extends Notification implements ShouldQueue
{
    use Queueable, RespectsPreferences;

    public function __construct(
        public Wedding $wedding,
        public int $daysOut,
    ) {}

    protected function marketingCategory(): ?string
    {
        return 'milestones';
    }

    private function headline(): string
    {
        return match ($this->daysOut) {
            1 => 'Tomorrow’s the day! 💍',
            7 => 'One week to go!',
            30 => '30 days until your wedding',
            default => $this->daysOut.' days to go!',
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pending = $this->wedding->guests()->where('rsvp_status', 'pending')->count();

        $mail = (new MailMessage)
            ->subject($this->headline().' — '.$this->wedding->name)
            ->greeting($this->headline())
            ->line('Your big day is almost here. Here’s a quick look at where things stand.');

        if ($pending > 0) {
            $mail->line("You’re still waiting on {$pending} RSVP".($pending === 1 ? '' : 's').'.');
        }

        $mail->action('Open your dashboard', url('/dashboard'));

        return $this->withCaslFooter($mail, $notifiable);
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => $this->wedding->name,
            'url' => '/dashboard',
            'icon' => 'calendar-heart',
        ];
    }
}
