<?php

namespace App\Http\Controllers;

use App\Enums\VendorCategory;
use App\Enums\VendorStatus;
use App\Http\Requests\VendorRequest;
use App\Models\Vendor;
use App\Support\CurrentWedding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $weddingId = $this->current->id();

        $vendors = Vendor::query()
            ->forWedding($weddingId)
            ->orderBy('name')
            ->get();

        return Inertia::render('vendors/index', [
            'vendors' => $vendors->map(fn (Vendor $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'category' => $v->category->value,
                'status' => $v->status->value,
                'rating' => $v->rating,
                'price_level' => $v->price_level,
                'contact_name' => $v->contact_name,
                'email' => $v->email,
                'phone' => $v->phone,
                'website' => $v->website,
                'cost' => $v->cost_cents !== null ? $v->cost_cents / 100 : null,
                'paid' => $v->paid_cents / 100,
                'notes' => $v->notes,
                'follow_up_at' => $v->follow_up_at?->toDateString(),
                'contract_status' => $v->contract_status,
                'coi_status' => $v->coi_status,
                'vendor_user_id' => $v->vendor_user_id,
            ]),
            'stats' => $this->stats($vendors),
            'options' => $this->options(),
            'quote_badge' => \App\Models\Inquiry::offersAwaiting($this->current->id()),
        ]);
    }

    public function store(VendorRequest $request): RedirectResponse
    {
        $vendor = new Vendor($this->fromRequest($request));
        $vendor->wedding_id = $this->current->id();
        $vendor->save();

        return back()->with('status', 'vendor-created');
    }

    public function update(VendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeTenant($vendor);

        $vendor->update($this->fromRequest($request));

        return back()->with('status', 'vendor-updated');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorizeTenant($vendor);

        $vendor->delete();

        return back()->with('status', 'vendor-deleted');
    }

    /** Side-by-side comparison of the vendors in one category. */
    public function compare(Request $request): Response
    {
        $weddingId = $this->current->id();

        $vendors = Vendor::query()->forWedding($weddingId)->orderBy('name')->get();

        // Categories that actually have vendors, for the selector.
        $categories = $vendors
            ->groupBy(fn (Vendor $v) => $v->category->value)
            ->map(fn (Collection $group, string $value) => [
                'value' => $value,
                'label' => VendorCategory::from($value)->label(),
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $active = $request->query('category');
        if (! $active || ! $categories->contains('value', $active)) {
            $active = $categories->first()['value'] ?? null;
        }

        $comparison = $this->buildComparison($vendors, $active);

        return Inertia::render('vendors/compare', [
            'categories' => $categories,
            'active' => $active,
            'vendors' => $comparison['vendors'],
            'bestValueId' => $comparison['bestValueId'],
            'statuses' => array_map(
                fn (VendorStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                VendorStatus::cases(),
            ),
        ]);
    }

    public function comparePdf(Request $request): \Illuminate\Http\Response
    {
        $weddingId = $this->current->id();
        $wedding = $this->current->get();

        $vendors = Vendor::query()->forWedding($weddingId)->orderBy('name')->get();
        $active = $request->query('category');

        if (! $active) {
            $active = $vendors->groupBy(fn (Vendor $v) => $v->category->value)
                ->map->count()
                ->sortDesc()
                ->keys()
                ->first();
        }

        $comparison = $this->buildComparison($vendors, $active);

        $pdf = Pdf::loadView('pdf.vendors-compare', [
            'wedding' => $wedding,
            'category' => $active ? VendorCategory::from($active)->label() : 'Vendors',
            'vendors' => $comparison['vendors'],
            'bestValueId' => $comparison['bestValueId'],
        ])->setPaper('a4', 'landscape');

        $filename = \Illuminate\Support\Str::slug($wedding->name).'-vendor-comparison.pdf';

        return $pdf->download($filename);
    }

    /**
     * Build the comparison rows for a category and pick the best-value vendor:
     * highest rating, tie-broken by the lower price level, then lower cost.
     *
     * @param  Collection<int, Vendor>  $vendors
     * @return array{vendors: array<int, array<string, mixed>>, bestValueId: int|null}
     */
    protected function buildComparison(Collection $vendors, ?string $category): array
    {
        $inCategory = $category
            ? $vendors->filter(fn (Vendor $v) => $v->category->value === $category)->values()
            : collect();

        $rated = $inCategory->filter(fn (Vendor $v) => $v->rating !== null);

        $bestValueId = null;
        if ($inCategory->count() >= 2 && $rated->isNotEmpty()) {
            // Best value: highest rating, then cheapest price level, then lowest cost.
            $bestValueId = $rated
                ->sort(fn (Vendor $a, Vendor $b) => [$b->rating, $a->price_level ?? 9, $a->cost_cents ?? PHP_INT_MAX]
                    <=> [$a->rating, $b->price_level ?? 9, $b->cost_cents ?? PHP_INT_MAX])
                ->first()?->id;
        }

        return [
            'vendors' => $inCategory->map(fn (Vendor $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'status' => $v->status->value,
                'rating' => $v->rating,
                'price_level' => $v->price_level,
                'cost' => $v->cost_cents !== null ? $v->cost_cents / 100 : null,
                'contact_name' => $v->contact_name,
                'email' => $v->email,
                'phone' => $v->phone,
                'website' => $v->website,
                'notes' => $v->notes,
            ])->all(),
            'bestValueId' => $bestValueId,
        ];
    }

    protected function authorizeTenant(Vendor $vendor): void
    {
        abort_unless($vendor->wedding_id === $this->current->id(), 404);
    }

    protected function fromRequest(VendorRequest $request): array
    {
        $data = $request->validated();

        return [
            'name' => $data['name'],
            'category' => $data['category'],
            'status' => $data['status'],
            'rating' => $data['rating'] ?? null,
            'price_level' => $data['price_level'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'cost_cents' => isset($data['cost_amount']) && $data['cost_amount'] !== null
                ? (int) round(((float) $data['cost_amount']) * 100)
                : null,
            'paid_cents' => (int) round(((float) $data['paid_amount']) * 100),
            'notes' => $data['notes'] ?? null,
            'follow_up_at' => isset($data['follow_up_at']) && $data['follow_up_at'] !== '' ? $data['follow_up_at'] : null,
            'contract_status' => isset($data['contract_status']) && $data['contract_status'] !== '' ? $data['contract_status'] : null,
            'coi_status' => isset($data['coi_status']) && $data['coi_status'] !== '' ? $data['coi_status'] : null,
        ];
    }

    /** @param Collection<int, Vendor> $vendors */
    protected function stats(Collection $vendors): array
    {
        return [
            'total' => $vendors->count(),
            'booked' => $vendors->where('status', VendorStatus::Booked)->count(),
            'contracted' => $vendors->sum(fn (Vendor $v) => $v->cost_cents ?? 0) / 100,
            'paid' => $vendors->sum('paid_cents') / 100,
        ];
    }

    protected function options(): array
    {
        $map = fn (array $cases) => array_map(
            fn ($c) => ['value' => $c->value, 'label' => $c->label()],
            $cases,
        );

        return [
            'categories' => $map(VendorCategory::cases()),
            'statuses' => $map(VendorStatus::cases()),
        ];
    }
}
