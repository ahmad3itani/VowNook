<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    /** Public contact form — forwards the message to platform admins. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:255'],
            'topic'   => ['required', 'in:couple,vendor,privacy,partnership,other'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $admins = User::where('is_admin', true)->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new ContactMessageReceived(
                $data['name'],
                $data['email'],
                $data['topic'],
                $data['message'],
            ));
        } else {
            // No admin account yet — fall back to the configured from-address.
            Notification::route('mail', config('mail.from.address'))
                ->notify(new ContactMessageReceived(
                    $data['name'],
                    $data['email'],
                    $data['topic'],
                    $data['message'],
                ));
        }

        return back()->with('status', 'contact-sent');
    }
}
