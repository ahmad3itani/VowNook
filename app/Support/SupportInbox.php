<?php

namespace App\Support;

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketReceived;
use Illuminate\Support\Facades\Notification;

/**
 * Opens a support ticket and alerts the admin team. Used by both the public
 * contact form (guest, no account) and the in-app "Help & support" form.
 */
class SupportInbox
{
    public static function open(array $attributes): SupportTicket
    {
        $ticket = SupportTicket::create($attributes);

        // Alerting admins must never break the requester's submission: a mail
        // transport failure (e.g. an invalid SMTP key) should not 500 the form.
        // The ticket is already saved and is the source of truth in the inbox.
        try {
            $admins = User::where('is_admin', true)->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new SupportTicketReceived($ticket));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $ticket;
    }
}
