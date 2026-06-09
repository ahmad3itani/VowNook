<?php

namespace App\Http\Controllers;

use App\Enums\SeatingElementType;
use App\Enums\TableShape;
use App\Http\Requests\SeatingTableRequest;
use App\Models\Guest;
use App\Models\SeatingElement;
use App\Models\SeatingTable;
use App\Support\CurrentWedding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    /** A printable floor plan (tables + chairs) and seating chart with meals & allergies. */
    public function exportPdf(): \Illuminate\Http\Response
    {
        $weddingId = $this->current->id();
        $wedding = $this->current->get();

        $tables = SeatingTable::query()->forWedding($weddingId)->orderBy('name')->get();
        $elements = SeatingElement::query()->forWedding($weddingId)->orderBy('id')->get();
        $guests = Guest::query()
            ->forWedding($weddingId)
            ->get(['id', 'first_name', 'last_name', 'table_id', 'seat_number', 'meal_choice', 'dietary_notes']);

        $rows = $tables->map(function (SeatingTable $t) use ($guests) {
            $seated = $guests
                ->where('table_id', $t->id)
                ->sortBy([['seat_number', 'asc'], ['first_name', 'asc']])
                ->map(fn (Guest $g) => [
                    'seat' => $g->seat_number,
                    'name' => trim($g->first_name.' '.($g->last_name ?? '')),
                    'meal' => $g->meal_choice,
                    'dietary' => $g->dietary_notes,
                ])
                ->values();

            return [
                'name' => $t->name,
                'shape' => $t->shape->label(),
                'capacity' => $t->capacity,
                'guests' => $seated,
            ];
        });

        $unseated = $guests
            ->whereNull('table_id')
            ->map(fn (Guest $g) => trim($g->first_name.' '.($g->last_name ?? '')))
            ->sort()
            ->values();

        $pdf = Pdf::loadView('pdf.seating', [
            'wedding' => $wedding,
            'tables' => $rows,
            'unseated' => $unseated,
            'plan' => $this->floorPlanGeometry($tables, $elements, $guests, $this->current->get()->seatingLayout),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(Str::slug($wedding->name).'-seating-chart.pdf');
    }

    /**
     * Pre-compute pixel-positioned table, chair and element geometry for the
     * printable floor plan, mirroring the on-screen canvas.
     */
    protected function floorPlanGeometry($tables, $elements, $guests, $layout): array
    {
        $rw = $layout?->room_width ?? 40;
        $rh = $layout?->room_height ?? 30;
        $scale = max(0.55, min(1.3, 46 / $rw));

        // Use most of an A4 landscape page so tables have room to breathe.
        $maxW = 980;
        $maxH = 600;
        $cw = $maxW;
        $ch = $cw * $rh / $rw;
        if ($ch > $maxH) {
            $ch = $maxH;
            $cw = $ch * $rw / $rh;
        }

        // The on-screen canvas sizes tables/chairs in fixed pixels; shrink them
        // for the (smaller) printed canvas so adjacent tables don't collide.
        $k = 0.7;

        $planTables = $tables->map(function (SeatingTable $t) use ($guests, $scale, $cw, $ch, $k) {
            $geo = $this->tableGeometry($t->shape->value, $t->capacity, $scale);
            $cx = $t->position_x / 100 * $cw;
            $cy = $t->position_y / 100 * $ch;
            $chairPx = $geo['chair'] * $k;

            $occupants = $guests->where('table_id', $t->id)->values();
            $seatMap = $this->resolveSeats($occupants, $t->capacity);

            $chairs = collect($geo['seats'])->map(function (array $s) use ($seatMap, $cx, $cy, $k) {
                $who = $seatMap[$s['n']] ?? null;
                $label = $who
                    ? mb_strtoupper(mb_substr($who->first_name, 0, 1).mb_substr($who->last_name ?? '', 0, 1))
                    : (string) $s['n'];

                return [
                    'x' => round($cx + $s['x'] * $k, 1),
                    'y' => round($cy + $s['y'] * $k, 1),
                    'label' => $label,
                    'occupied' => $who !== null,
                ];
            })->all();

            return [
                'cx' => round($cx, 1),
                'cy' => round($cy, 1),
                'w' => round($geo['tableW'] * $k, 1),
                'h' => round($geo['tableH'] * $k, 1),
                'round' => $t->shape->value === 'round',
                'chair' => round($chairPx, 1),
                'name' => $t->name,
                'chairs' => $chairs,
            ];
        })->all();

        $planElements = $elements->map(function (SeatingElement $e) use ($cw, $ch) {
            return [
                'w' => round($e->width / 100 * $cw, 1),
                'h' => round($e->height / 100 * $ch, 1),
                'cx' => round($e->position_x / 100 * $cw, 1),
                'cy' => round($e->position_y / 100 * $ch, 1),
                'label' => $e->label ?? $e->type->label(),
            ];
        })->all();

        return [
            'width' => round($cw, 1),
            'height' => round($ch, 1),
            'room_width' => $rw,
            'room_height' => $rh,
            'tables' => $planTables,
            'elements' => $planElements,
        ];
    }

    /** @return array{tableW: float, tableH: float, chair: float, seats: array<int, array{n:int,x:float,y:float}>} */
    protected function tableGeometry(string $shape, int $capacity, float $scale): array
    {
        $chair = 22 * $scale;
        $cap = max(1, $capacity);

        if ($shape === 'rectangle' || $shape === 'square') {
            $perRow = (int) ceil($cap / 2);
            $tableW = $shape === 'square'
                ? max(54, $perRow * ($chair + 6)) * 0.8
                : max(60, $perRow * ($chair + 8));
            $tableH = $shape === 'square' ? $tableW : 40 * $scale;

            $seats = [];
            $top = (int) ceil($cap / 2);
            $bottom = $cap - $top;
            $place = function (int $count, float $y, int $startN) use (&$seats, $tableW, $chair) {
                for ($i = 0; $i < $count; $i++) {
                    $t = $count === 1 ? 0.5 : $i / ($count - 1);
                    $seats[] = ['n' => $startN + $i, 'x' => ($t - 0.5) * ($tableW - $chair), 'y' => $y];
                }
            };
            $place($top, -($tableH / 2 + $chair * 0.7), 1);
            $place($bottom, $tableH / 2 + $chair * 0.7, $top + 1);

            return ['tableW' => $tableW, 'tableH' => $tableH, 'chair' => $chair, 'seats' => $seats];
        }

        $minRadius = ($cap * ($chair + 6)) / (2 * M_PI);
        $radius = max(30 * $scale, $minRadius);
        $diameter = max(48 * $scale, $radius * 1.15);
        $seats = [];
        for ($i = 0; $i < $cap; $i++) {
            $angle = -M_PI / 2 + ($i / $cap) * 2 * M_PI;
            $seats[] = [
                'n' => $i + 1,
                'x' => cos($angle) * ($radius + $chair * 0.55),
                'y' => sin($angle) * ($radius + $chair * 0.55),
            ];
        }

        return ['tableW' => $diameter, 'tableH' => $diameter, 'chair' => $chair, 'seats' => $seats];
    }

    /** Resolve seat number => guest, filling blanks in order. */
    protected function resolveSeats($occupants, int $capacity): array
    {
        $map = [];
        $leftovers = [];

        foreach ($occupants as $g) {
            $sn = $g->seat_number;
            if ($sn && $sn >= 1 && $sn <= $capacity && ! isset($map[$sn])) {
                $map[$sn] = $g;
            } else {
                $leftovers[] = $g;
            }
        }

        $n = 1;
        foreach ($leftovers as $g) {
            while ($n <= $capacity && isset($map[$n])) {
                $n++;
            }
            if ($n > $capacity) {
                break;
            }
            $map[$n] = $g;
        }

        return $map;
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
