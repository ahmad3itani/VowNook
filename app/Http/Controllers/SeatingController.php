<?php

namespace App\Http\Controllers;

use App\Enums\SeatingElementType;
use App\Enums\TableShape;
use App\Http\Requests\SeatingTableRequest;
use App\Models\Guest;
use App\Models\SeatingElement;
use App\Models\SeatingTable;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SeatingController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $weddingId = $this->current->id();
        $wedding = $this->current->get();

        $tables = SeatingTable::query()
            ->forWedding($weddingId)
            ->withCount('guests')
            ->orderBy('name')
            ->get();

        $guests = Guest::query()
            ->forWedding($weddingId)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'table_id', 'seat_number', 'rsvp_status']);

        $elements = SeatingElement::query()
            ->forWedding($weddingId)
            ->orderBy('id')
            ->get();

        $layout = $wedding->seatingLayout;
        $seated = $guests->whereNotNull('table_id')->count();

        return Inertia::render('seating/index', [
            'tables' => $tables->map(fn (SeatingTable $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'shape' => $t->shape->value,
                'capacity' => $t->capacity,
                'position_x' => $t->position_x,
                'position_y' => $t->position_y,
                'notes' => $t->notes,
                'seated' => $t->guests_count,
            ]),
            'guests' => $guests->map(fn (Guest $g) => [
                'id' => $g->id,
                'name' => trim($g->first_name.' '.($g->last_name ?? '')),
                'table_id' => $g->table_id,
                'seat_number' => $g->seat_number,
                'rsvp_status' => $g->rsvp_status->value,
            ]),
            'elements' => $elements->map(fn (SeatingElement $e) => [
                'id' => $e->id,
                'type' => $e->type->value,
                'label' => $e->label ?? $e->type->label(),
                'position_x' => $e->position_x,
                'position_y' => $e->position_y,
                'width' => $e->width,
                'height' => $e->height,
                'rotation' => $e->rotation,
            ]),
            'layout' => [
                'room_width' => $layout?->room_width ?? 40,
                'room_height' => $layout?->room_height ?? 30,
            ],
            'stats' => [
                'tables' => $tables->count(),
                'capacity' => $tables->sum('capacity'),
                'seated' => $seated,
                'unseated' => $guests->count() - $seated,
            ],
            'options' => [
                'shapes' => array_map(
                    fn (TableShape $s) => ['value' => $s->value, 'label' => $s->label()],
                    TableShape::cases(),
                ),
                'elementTypes' => array_map(
                    fn (SeatingElementType $t) => [
                        'value' => $t->value,
                        'label' => $t->label(),
                        'size' => $t->defaultSize(),
                    ],
                    SeatingElementType::cases(),
                ),
            ],
        ]);
    }

    public function store(SeatingTableRequest $request): RedirectResponse
    {
        $table = new SeatingTable($request->validated());
        $table->wedding_id = $this->current->id();
        $table->save();

        return back()->with('status', 'table-created');
    }

    public function update(SeatingTableRequest $request, SeatingTable $table): RedirectResponse
    {
        $this->authorizeTenant($table);

        $table->update($request->validated());

        return back()->with('status', 'table-updated');
    }

    public function destroy(SeatingTable $table): RedirectResponse
    {
        $this->authorizeTenant($table);

        $table->delete();

        return back()->with('status', 'table-deleted');
    }

    /** Persist a dragged table's new position without a full form round-trip. */
    public function move(Request $request, SeatingTable $table): RedirectResponse
    {
        $this->authorizeTenant($table);

        $data = $request->validate([
            'position_x' => ['required', 'integer', 'min:0', 'max:100'],
            'position_y' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $table->update($data);

        return back()->with('status', 'table-moved');
    }

    /** Update the room dimensions for the floor plan. */
    public function updateLayout(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();

        $data = $request->validate([
            'room_width' => ['required', 'integer', 'min:10', 'max:200'],
            'room_height' => ['required', 'integer', 'min:10', 'max:200'],
        ]);

        $wedding->seatingLayout()->updateOrCreate(['wedding_id' => $wedding->id], $data);

        return back()->with('status', 'layout-updated');
    }

    /** Seat a guest at a table and optional chair (or unseat when table_id is null). */
    public function assign(Request $request): RedirectResponse
    {
        $weddingId = $this->current->id();

        $data = $request->validate([
            'guest_id' => ['required', 'integer'],
            'table_id' => ['nullable', 'integer'],
            'seat_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $guest = Guest::query()->forWedding($weddingId)->findOrFail($data['guest_id']);

        if ($data['table_id'] === null) {
            $guest->update(['table_id' => null, 'seat_number' => null]);

            return back()->with('status', 'guest-assigned');
        }

        $table = SeatingTable::query()
            ->forWedding($weddingId)
            ->withCount('guests')
            ->findOrFail($data['table_id']);

        $seat = $data['seat_number'] ?? null;

        if ($seat !== null && $seat > $table->capacity) {
            throw ValidationException::withMessages([
                'seat_number' => "{$table->name} only has {$table->capacity} seats.",
            ]);
        }

        // A specific chair can hold only one guest.
        if ($seat !== null) {
            $taken = Guest::query()
                ->forWedding($weddingId)
                ->where('table_id', $table->id)
                ->where('seat_number', $seat)
                ->where('id', '!=', $guest->id)
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages([
                    'seat_number' => 'That seat is already taken.',
                ]);
            }
        }

        $movingTables = $guest->table_id !== $table->id;
        if ($movingTables && $table->guests_count >= $table->capacity) {
            throw ValidationException::withMessages([
                'table_id' => "{$table->name} is already full.",
            ]);
        }

        $guest->update(['table_id' => $table->id, 'seat_number' => $seat]);

        return back()->with('status', 'guest-assigned');
    }

    protected function authorizeTenant(SeatingTable $table): void
    {
        abort_unless($table->wedding_id === $this->current->id(), 404);
    }
}
