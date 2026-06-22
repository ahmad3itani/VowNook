<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
use App\Support\PlanFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets an admin unlock or re-lock premium features for the FREE tier — a global
 * lever for the guest experience. Paid plans are unaffected.
 */
class FeatureController extends Controller
{
    public function index(): Response
    {
        $enabled = PlanFeatures::freeTierMap();

        $features = array_map(fn (string $key) => [
            'key' => $key,
            'label' => PlanFeatures::FEATURES[$key]['label'],
            'description' => PlanFeatures::FEATURES[$key]['description'],
            'enabled' => $enabled[$key],
            'paid_by_default' => ! PlanFeatures::freeTierDefault($key),
        ], PlanFeatures::keys());

        return Inertia::render('admin/features', [
            'features' => $features,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = ['features' => ['required', 'array']];

        foreach (PlanFeatures::keys() as $key) {
            $rules["features.{$key}"] = ['sometimes', 'boolean'];
        }

        $data = $request->validate($rules);

        PlanFeatures::save($data['features']);

        ActivityLogger::log('admin.features.update', null, [
            'free_tier' => PlanFeatures::freeTierMap(),
        ]);

        return back()->with('status', 'features-updated');
    }
}
