<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Weekly planning summary: open tasks, RSVP stats, quotes awaiting a reply. */
class WeeklyDigest extends Notification implements ShouldQueue
{
    use Queueable, RespectsPreferences;

    /** @param array{wedding:string,open_tasks:int,pending_rsvps:int,open_quotes:int,days_out:?int} $stats */
    public function __construct(public array $stats) {}

    protected function marketingCategory(): ?string
    {
        return 'digest';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->stats;

        $mail = (new MailMessage)
            ->subject('Your weekly wedding update — '.$s['wedding'])
            ->greeting('Your week at a glance');

        if ($s['days_out'] !== null) {
            $mail->line($s['days_out'].' days until the big day.');
        }

        $mail->line('• '.$s['open_tasks'].' open task'.($s['open_tasks'] === 1 ? '' : 's'))
            ->line('• '.$s['pending_rsvps'].' RSVP'.($s['pending_rsvps'] === 1 ? '' : 's').' still pending')
            ->line('• '.$s['open_quotes'].' vendor quote'.($s['open_quotes'] === 1 ? '' : 's').' awaiting your reply')
            ->action('Open your dashboard', url('/dashboard'));

        return $this->withCaslFooter($mail, $notifiable);
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Your weekly wedding update',
            'body' => $this->stats['open_tasks'].' open tasks · '.$this->stats['pending_rsvps'].' pending RSVPs',
            'url' => '/dashboard',
            'icon' => 'calendar-clock',
        ];
    }
}
