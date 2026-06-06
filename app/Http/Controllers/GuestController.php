<?php

namespace App\Http\Controllers;

use App\Enums\AgeGroup;
use App\Enums\GuestSide;
use App\Enums\RsvpStatus;
use App\Http\Requests\GuestRequest;
use App\Models\Guest;
use App\Support\CurrentWedding;
use App\Support\PlanLimits;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    public function __construct(
        protected CurrentWedding $current,
        protected PlanLimits $limits,
    ) {}

    public function index(): Response
    {
        $weddingId = $this->current->id();

        $guests = Guest::query()
            ->forWedding($weddingId)
            ->with('group:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('guests/index', [
            'guests' => $guests->map(fn (Guest $g) => [
                'id' => $g->id,
                'first_name' => $g->first_name,
                'last_name' => $g->last_name,
                'email' => $g->email,
                'phone' => $g->phone,
                'side' => $g->side->value,
                'age_group' => $g->age_group->value,
                'is_plus_one' => $g->is_plus_one,
                'rsvp_status' => $g->rsvp_status->value,
                'meal_choice' => $g->meal_choice,
                'dietary_notes' => $g->dietary_notes,
                'notes' => $g->notes,
                'group_id' => $g->group_id,
                'group_name' => $g->group?->name,
            ]),
            'groups' => $this->current->get()?->guestGroups()
                ->orderBy('name')
                ->get(['id', 'name', 'notes']) ?? [],
            'stats' => $this->stats($guests),
            'options' => $this->options(),
            'plan' => [
                'used' => $guests->count(),
                'limit' => $this->limits->limit($this->current->get(), 'max_guests_per_wedding'),
            ],
        ]);
    }

    public function store(GuestRequest $request): RedirectResponse
    {
        $this->limits->enforceGuests($this->current->get());

        $guest = new Guest($request->validated());
        $guest->wedding_id = $this->current->id();
        $guest->save();

        return back()->with('status', 'guest-created');
    }

    public function update(GuestRequest $request, Guest $guest): RedirectResponse
    {
        $this->authorizeTenant($guest);

        $guest->update($request->validated());

        return back()->with('status', 'guest-updated');
    }

    public function destroy(Guest $guest): RedirectResponse
    {
        $this->authorizeTenant($guest);

        $guest->delete();

        return back()->with('status', 'guest-deleted');
    }

    /** Guard against acting on a guest from another wedding. */
    protected function authorizeTenant(Guest $guest): void
    {
        abort_unless($guest->wedding_id === $this->current->id(), 404);
    }

    /** @param \Illuminate\Support\Collection<int, Guest> $guests */
    protected function stats($guests): array
    {
        return [
            'total' => $guests->count(),
            'attending' => $guests->where('rsvp_status', RsvpStatus::Attending)->count(),
            'declined' => $guests->where('rsvp_status', RsvpStatus::Declined)->count(),
            'pending' => $guests->where('rsvp_status', RsvpStatus::Pending)->count(),
            'maybe' => $guests->where('rsvp_status', RsvpStatus::Maybe)->count(),
        ];
    }

    protected function options(): array
    {
        $map = fn (array $cases) => array_map(
            fn ($c) => ['value' => $c->value, 'label' => $c->label()],
            $cases,
        );

        return [
            'sides' => $map(GuestSide::cases()),
            'ageGroups' => $map(AgeGroup::cases()),
            'statuses' => $map(RsvpStatus::cases()),
        ];
    }
}
