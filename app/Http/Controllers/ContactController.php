<?php

namespace App\Http\Controllers;

use App\Support\SupportInbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /** Public contact form — opens a support ticket and alerts admins. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:255'],
            'topic'   => ['required', 'in:couple,vendor,privacy,partnership,other'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        SupportInbox::open([
            'user_id' => $request->user()?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => 'Contact: '.ucfirst($data['topic']),
            'category' => $this->categoryFor($data['topic']),
            'message' => $data['message'],
            'source' => 'contact',
        ]);

        return back()->with('status', 'contact-sent');
    }

    private function categoryFor(string $topic): string
    {
        return match ($topic) {
            'vendor' => 'vendor',
            'privacy' => 'abuse',
            'partnership' => 'general',
            default => 'general',
        };
    }
}
