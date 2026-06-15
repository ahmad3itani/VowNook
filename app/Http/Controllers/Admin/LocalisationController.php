<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LocalisationController extends Controller
{
    /**
     * The UI strings exposed for translation, with their default (English) copy.
     */
    public const STRINGS = [
        'app.tagline' => 'A wedding, composed.',
        'dashboard.welcome' => 'Welcome back',
        'cta.rsvp' => 'RSVP',
        'cta.find_seat' => 'Find your seat',
        'public.rsvp_heading' => 'Kindly Respond',
        'public.rsvp_subheading' => 'Find your name to reply.',
        'public.seat_heading' => 'Find your seat',
        'public.footer' => 'Made with VowNook',
    ];

    public function index(Request $request): Response
    {
        $locale = $request->query('locale', 'en');
        if (! array_key_exists($locale, Translation::LOCALES)) {
            $locale = 'en';
        }

        $stored = Translation::query()->where('locale', $locale)->pluck('value', 'key')->all();

        $strings = collect(self::STRINGS)
            ->map(fn (string $default, string $key) => [
                'key' => $key,
                'default' => $default,
                'value' => $stored[$key] ?? null,
            ])
            ->values();

        return Inertia::render('admin/localisation', [
            'locales' => collect(Translation::LOCALES)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'active' => $locale,
            'strings' => $strings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(array_keys(Translation::LOCALES))],
            'strings' => ['array'],
            'strings.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $locale = $data['locale'];

        foreach ($data['strings'] ?? [] as $key => $value) {
            if (! array_key_exists($key, self::STRINGS)) {
                continue;
            }

            $value = is_string($value) ? trim($value) : null;

            if ($value === null || $value === '') {
                Translation::query()->where('locale', $locale)->where('key', $key)->delete();
            } else {
                Translation::put($locale, $key, $value);
            }
        }

        Translation::flush($locale);

        return back()->with('status', 'localisation-updated');
    }
}
