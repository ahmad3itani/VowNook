<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Keys exposed in the admin Branding tab and their validation rules.
     */
    protected array $brandingRules = [
        'app_name' => ['nullable', 'string', 'max:100'],
        'brand_primary' => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
        'brand_tagline' => ['nullable', 'string', 'max:160'],
    ];

    public function index(): Response
    {
        $settings = Setting::all();

        return Inertia::render('admin/settings', [
            'settings' => [
                'app_name' => $settings['app_name'] ?? config('app.name'),
                'brand_primary' => $settings['brand_primary'] ?? config('branding.primary'),
                'brand_tagline' => $settings['brand_tagline'] ?? config('branding.tagline'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->brandingRules);

        foreach ($validated as $key => $value) {
            Setting::put($key, $value, 'branding');
        }

        return back()->with('status', 'settings-updated');
    }
}
