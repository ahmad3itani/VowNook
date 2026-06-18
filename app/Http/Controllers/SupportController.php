<?php

namespace App\Http\Controllers;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Support\SupportInbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-app "Help & support" for signed-in couples, vendors and planners: open a
 * ticket, see their conversation history, and reply. Scoped to the owner.
 */
class SupportController extends Controller
{
    public function index(Request $request): Response
    {
        $tickets = $request->user()->supportTickets()
            ->latest()
            ->get(['id', 'subject', 'category', 'status', 'last_reply_at', 'created_at'])
            ->map(fn (SupportTicket $t) => [
                'id' => $t->id,
                'subject' => $t->subject,
                'category' => $t->category,
                'status' => $t->status->value,
                'status_label' => $t->status->label(),
                'last_reply_at' => $t->last_reply_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
            ]);

        return Inertia::render('support/index', [
            'tickets' => $tickets,
            'categories' => SupportTicket::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:'.implode(',', SupportTicket::CATEGORIES)],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SupportInbox::open([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'subject' => $data['subject'],
            'category' => $data['category'],
            'message' => $data['message'],
            'source' => 'in_app',
        ]);

        return redirect()->route('support.show', $ticket)->with('status', 'ticket-opened');
    }

    public function show(Request $request, SupportTicket $ticket): Response
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        $ticket->load('replies.author:id,name');

        return Inertia::render('support/show', [
            'ticket' => $this->serialize($ticket),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->replies()->create([
            'author_id' => $request->user()->id,
            'is_staff' => false,
            'body' => $data['body'],
        ]);

        // A customer reply reopens a closed/pending ticket for staff attention.
        $ticket->forceFill([
            'status' => SupportTicketStatus::Open,
            'last_reply_at' => now(),
            'closed_at' => null,
        ])->save();

        return back()->with('status', 'reply-sent');
    }

    private function serialize(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label(),
            'message' => $ticket->message,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'replies' => $ticket->replies->map(fn ($r) => [
                'id' => $r->id,
                'body' => $r->body,
                'is_staff' => $r->is_staff,
                'author' => $r->is_staff ? (config('app.name').' Support') : ($r->author?->name ?? 'You'),
                'created_at' => $r->created_at?->toIso8601String(),
            ]),
        ];
    }
}
