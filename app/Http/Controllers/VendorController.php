<?php

namespace App\Http\Controllers;

use App\Enums\VendorCategory;
use App\Enums\VendorStatus;
use App\Http\Requests\VendorRequest;
use App\Models\Vendor;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
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
                'contact_name' => $v->contact_name,
                'email' => $v->email,
                'phone' => $v->phone,
                'website' => $v->website,
                'cost' => $v->cost_cents !== null ? $v->cost_cents / 100 : null,
                'paid' => $v->paid_cents / 100,
                'notes' => $v->notes,
            ]),
            'stats' => $this->stats($vendors),
            'options' => $this->options(),
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
            'contact_name' => $data['contact_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'cost_cents' => isset($data['cost_amount']) && $data['cost_amount'] !== null
                ? (int) round(((float) $data['cost_amount']) * 100)
                : null,
            'paid_cents' => (int) round(((float) $data['paid_amount']) * 100),
            'notes' => $data['notes'] ?? null,
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
