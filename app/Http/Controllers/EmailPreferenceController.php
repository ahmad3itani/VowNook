<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EmailPreferences;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One-click unsubscribe reached from a signed link in the email footer (CASL).
 * No login required — the signed URL proves authenticity.
 */
class EmailPreferenceController extends Controller
{
    public function unsubscribe(User $user, string $category): Response
    {
        abort_unless(array_key_exists($category, EmailPreferences::CATEGORIES), 404);

        $prefs = EmailPreferences::forUser($user);
        $prefs[$category] = false;
        $user->update(['email_preferences' => $prefs]);

        return Inertia::render('email/unsubscribed', [
            'category' => EmailPreferences::CATEGORIES[$category],
        ]);
    }
}
