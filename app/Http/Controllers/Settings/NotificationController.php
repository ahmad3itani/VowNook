<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\EmailPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/notifications', [
            'categories' => EmailPreferences::CATEGORIES,
            'preferences' => EmailPreferences::forUser($request->user()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*' => ['boolean'],
        ]);

        $request->user()->update([
            'email_preferences' => EmailPreferences::normalize($data['preferences']),
        ]);

        return back()->with('status', 'notification-preferences-updated');
    }
}
