<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Gentle nudge to couples who haven't completed key setup steps. */
class OnboardingNudge extends Notification implements ShouldQueue
{
    use Queueable, RespectsPreferences;

    /** @param list<array{label:string,url:string}> $steps */
    public function __construct(public array $steps) {}

    protected function marketingCategory(): ?string
    {
        return 'planning_tips';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('A few quick wins for your wedding planning')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('You’re off to a great start. Here are a couple of things to do next:');

        foreach ($this->steps as $step) {
            $mail->line('• '.$step['label']);
        }

        $first = $this->steps[0] ?? ['url' => '/dashboard'];
        $mail->action('Pick up where you left off', url($first['url']));

        return $this->withCaslFooter($mail, $notifiable);
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'A few quick wins for your planning',
            'body' => ($this->steps[0]['label'] ?? 'Continue setting up your wedding'),
            'url' => $this->steps[0]['url'] ?? '/dashboard',
            'icon' => 'list-checks',
        ];
    }
}
