<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\GuestBroadcast;
use App\Models\Wedding;
use App\Notifications\GuestBroadcastMessage;
use App\Support\CurrentWedding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Message your guests" — the couple writes one announcement and sends it to a
 * chosen audience (everyone / attending / not yet replied / maybe). Sent on the
 * couple's behalf to guests' emails; each send is logged for a history list.
 */
class GuestBroadcastController extends Controller
{
    public const AUDIENCES = ['all', 'attending', 'pending', 'maybe'];

    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();

        $counts = [];
        foreach (self::AUDIENCES as $audience) {
            $counts[$audience] = $this->audienceQuery($wedding, $audience)->count();
        }

        $history = GuestBroadcast::forWedding($wedding->id)->latest('id')->limit(30)->get()
            ->map(fn (GuestBroadcast $b) => [
                'id' => $b->id,
                'subject' => $b->subject,
                'body' => $b->body,
                'audience' => $b->audience,
                'recipient_count' => $b->recipient_count,
                'sent_at' => $b->sent_at?->toIso8601String(),
            ]);

        return Inertia::render('communications/index', [
            'counts' => $counts,
            'history' => $history,
            'audiences' => self::AUDIENCES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'audience' => ['required', 'in:'.implode(',', self::AUDIENCES)],
        ]);

        $wedding = $this->current->get();
        $guests = $this->audienceQuery($wedding, $data['audience'])->get();

        foreach ($guests as $guest) {
            Notification::route('mail', $guest->email)
                ->notify(new GuestBroadcastMessage($wedding, $guest, $data['subject'], $data['body']));
        }

        GuestBroadcast::create([
            'wedding_id' => $wedding->id,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'recipient_count' => $guests->count(),
            'sent_at' => now(),
        ]);

        return back()->with('status', "broadcast-sent:{$guests->count()}");
    }

    /** Guests with a usable email, optionally filtered by RSVP status. */
    private function audienceQuery(Wedding $wedding, string $audience): Builder
    {
        $query = Guest::query()
            ->forWedding($wedding->id)
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if (in_array($audience, ['attending', 'pending', 'maybe'], true)) {
            $query->where('rsvp_status', $audience);
        }

        return $query;
    }
}
