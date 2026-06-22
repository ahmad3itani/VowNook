<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Notifications\SupportTicketReplied;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Admin support inbox: triage, assign, reply to and close tickets. */
class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'open');

        $tickets = SupportTicket::query()
            ->with('assignee:id,name')
            ->when($status === 'open', fn ($q) => $q->open())
            ->when(in_array($status, ['pending', 'closed'], true), fn ($q) => $q->where('status', $status))
            ->latest()
            ->limit(150)
            ->get()
            ->map(fn (SupportTicket $t) => [
                'id' => $t->id,
                'subject' => $t->subject,
                'name' => $t->name,
                'email' => $t->email,
                'category' => $t->category,
                'status' => $t->status->value,
                'status_label' => $t->status->label(),
                'source' => $t->source,
                'assignee' => $t->assignee?->name,
                'last_reply_at' => $t->last_reply_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/support-index', [
            'tickets' => $tickets,
            'filter' => ['status' => $status],
            'counts' => [
                'open' => SupportTicket::open()->count(),
                'all' => SupportTicket::count(),
            ],
        ]);
    }

    public function show(SupportTicket $ticket): Response
    {
        $ticket->load(['replies.author:id,name', 'user:id,name,email,account_type', 'assignee:id,name']);

        return Inertia::render('admin/support-show', [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'name' => $ticket->name,
                'email' => $ticket->email,
                'category' => $ticket->category,
                'status' => $ticket->status->value,
                'status_label' => $ticket->status->label(),
                'source' => $ticket->source,
                'message' => $ticket->message,
                'assignee' => $ticket->assignee?->name,
                'user' => $ticket->user ? [
                    'id' => $ticket->user->id,
                    'name' => $ticket->user->name,
                    'account_type' => $ticket->user->account_type->value,
                ] : null,
                'created_at' => $ticket->created_at?->toIso8601String(),
                'replies' => $ticket->replies->map(fn ($r) => [
                    'id' => $r->id,
                    'body' => $r->body,
                    'is_staff' => $r->is_staff,
                    'author' => $r->author?->name ?? ($r->is_staff ? 'Support' : $ticket->name),
                    'created_at' => $r->created_at?->toIso8601String(),
                ]),
            ],
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], SupportTicketStatus::cases()),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->replies()->create([
            'author_id' => $request->user()->id,
            'is_staff' => true,
            'body' => $data['body'],
        ]);

        $ticket->forceFill([
            'status' => SupportTicketStatus::Pending,
            'last_reply_at' => now(),
            'assigned_to' => $ticket->assigned_to ?? $request->user()->id,
        ])->save();

        // Notify the requester — but a mail failure must not 500 the reply (the
        // reply is already saved; the requester sees it in their thread).
        try {
            if ($ticket->user) {
                $ticket->user->notify(new SupportTicketReplied($ticket, $data['body']));
            } else {
                Notification::route('mail', $ticket->email)
                    ->notify(new SupportTicketReplied($ticket, $data['body']));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        ActivityLogger::log('admin.support.reply', $ticket);

        return back()->with('status', 'reply-sent');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(SupportTicketStatus::class)],
        ]);

        $status = SupportTicketStatus::from($data['status']);

        $ticket->forceFill([
            'status' => $status,
            'closed_at' => $status === SupportTicketStatus::Closed ? now() : null,
        ])->save();

        ActivityLogger::log('admin.support.status', $ticket, ['status' => $status->value]);

        return back()->with('status', 'status-updated');
    }

    public function assign(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $ticket->forceFill(['assigned_to' => $request->user()->id])->save();

        ActivityLogger::log('admin.support.assign', $ticket);

        return back()->with('status', 'assigned');
    }
}
