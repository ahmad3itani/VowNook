<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Support\EmailPreferences;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

/**
 * Shared channel + CASL behaviour for lifecycle/marketing notifications.
 *
 * A notification using this trait declares a `marketingCategory()`:
 *  - returns null  => transactional: always mail + database.
 *  - returns a key => marketing: database always; mail only if the user hasn't
 *    opted out of that category. Marketing mail also gets a CASL-compliant
 *    footer (sender identity + a working unsubscribe link).
 */
trait RespectsPreferences
{
    /** Override in marketing notifications; null = transactional. */
    protected function marketingCategory(): ?string
    {
        return null;
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $category = $this->marketingCategory();

        // Transactional, or a non-User notifiable (e.g. on-demand mail): just mail+db.
        if ($category === null || ! $notifiable instanceof User) {
            return $notifiable instanceof User ? ['mail', 'database'] : ['mail'];
        }

        $channels = ['database'];

        if (EmailPreferences::wants($notifiable, $category)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /** Append the CASL footer (sender identity + unsubscribe) to marketing mail. */
    protected function withCaslFooter(MailMessage $mail, object $notifiable): MailMessage
    {
        $category = $this->marketingCategory();

        if ($category !== null && $notifiable instanceof User) {
            $url = URL::signedRoute('email.unsubscribe', [
                'user' => $notifiable->getKey(),
                'category' => $category,
            ]);
            $mail->line('---');
            $mail->line('You’re receiving this because you have an account with '.config('app.name').'.');
            $mail->action('Unsubscribe from these emails', $url);
        }

        $mail->salutation(config('app.name').' · '.config('mail.casl_address'));

        return $mail;
    }
}
