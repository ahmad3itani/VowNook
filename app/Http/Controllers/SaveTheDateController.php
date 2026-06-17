<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\GuestSend;
use App\Notifications\GuestSaveTheDate;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Send save-the-dates / invitations to guests and track opens. Each send embeds
 * a unique tracking-pixel token; the delivery dashboard shows sent / opened /
 * responded counts per kind.
 */
class SaveTheDateController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();

        $withEmail = $this->emailableGuests($wedding->id)->count();

        $stats = [];
        foreach (GuestSend::KINDS as $kind) {
            $sends = GuestSend::forWedding($wedding->id)->where('kind', $kind);
            $sentGuestIds = (clone $sends)->whereNotNull('sent_at')->pluck('guest_id');

            $stats[$kind] = [
                'sent' => $sentGuestIds->count(),
                'opened' => (clone $sends)->whereNotNull('opened_at')->count(),
                'responded' => $sentGuestIds->isEmpty() ? 0 : Guest::query()
                    ->whereIn('id', $sentGuestIds)
                    ->where('rsvp_status', '!=', 'pending')
                    ->count(),
            ];
        }

        return Inertia::render('save-the-dates/index', [
            'stats' => $stats,
            'guests_with_email' => $withEmail,
            'kinds' => GuestSend::KINDS,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:'.implode(',', GuestSend::KINDS)],
        ]);

        $wedding = $this->current->get();
        $guests = $this->emailableGuests($wedding->id)->get();

        foreach ($guests as $guest) {
            $token = Str::random(40);

            // One row per (guest, kind) — re-sending refreshes the token + resets opens.
            GuestSend::updateOrCreate(
                ['guest_id' => $guest->id, 'kind' => $data['kind']],
                ['wedding_id' => $wedding->id, 'token' => $token, 'sent_at' => now(), 'opened_at' => null],
            );

            Notification::route('mail', $guest->email)
                ->notify(new GuestSaveTheDate($wedding, $guest, $data['kind'], $token));
        }

        return back()->with('status', "save-the-dates-sent:{$guests->count()}");
    }

    private function emailableGuests(int $weddingId)
    {
        return Guest::query()
            ->forWedding($weddingId)
            ->whereNotNull('email')
            ->where('email', '!=', '');
    }
}
