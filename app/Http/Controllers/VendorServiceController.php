<?php

namespace App\Http\Controllers;

use App\Models\VendorService;
use App\Support\CurrentVendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorServiceController extends Controller
{
    public function __construct(protected CurrentVendorProfile $current) {}

    public function index(): Response
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $services = VendorService::forVendorProfile($profile->id)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->map(fn (VendorService $s) => $this->serialize($s));

        return Inertia::render('vendor/services', [
            'services' => $services,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $data = $this->validated($request);

        $maxOrder = VendorService::forVendorProfile($profile->id)->max('sort_order') ?? 0;

        VendorService::create([
            ...$data,
            'vendor_profile_id' => $profile->id,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('status', 'service-created');
    }

    public function update(Request $request, VendorService $service): RedirectResponse
    {
        $this->authorizeTenant($service);

        $service->update($this->validated($request));

        return back()->with('status', 'service-updated');
    }

    public function toggle(VendorService $service): RedirectResponse
    {
        $this->authorizeTenant($service);

        $service->update(['is_active' => !$service->is_active]);

        return back()->with('status', 'service-toggled');
    }

    public function destroy(VendorService $service): RedirectResponse
    {
        $this->authorizeTenant($service);

        $service->delete();

        return back()->with('status', 'service-deleted');
    }

    // -----------------------------------------------------------------------

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'price_unit' => ['nullable', 'string', 'in:per_event,per_hour,per_person'],
            'price_type' => ['required', 'string', 'in:fixed,from,quote_only'],
            'is_active' => ['boolean'],
        ]);
    }

    private function authorizeTenant(VendorService $service): void
    {
        abort_unless($service->vendor_profile_id === $this->current->id(), 404);
    }

    private function serialize(VendorService $s): array
    {
        return [
            'id' => $s->id,
            'name' => $s->name,
            'description' => $s->description,
            'price_cents' => $s->price_cents,
            'price_unit' => $s->price_unit,
            'price_type' => $s->price_type,
            'is_active' => $s->is_active,
            'sort_order' => $s->sort_order,
        ];
    }
}
