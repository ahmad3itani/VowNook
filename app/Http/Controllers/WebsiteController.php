<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeddingWebsiteRequest;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The authenticated editor for a couple's public wedding website.
 */
class WebsiteController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();
        $website = $wedding->website;

        return Inertia::render('website/index', [
            'website' => [
                'is_published' => (bool) ($website?->is_published ?? false),
                'headline' => $website?->headline,
                'welcome_message' => $website?->welcome_message,
                'our_story' => $website?->our_story,
                'venue_name' => $website?->venue_name,
                'venue_address' => $website?->venue_address,
                'ceremony_time' => $website?->ceremony_time,
                'dress_code' => $website?->dress_code,
                'hero_image_url' => $website?->hero_image_url,
            ],
            'public_url' => route('public.website', $wedding),
        ]);
    }

    public function update(WeddingWebsiteRequest $request): RedirectResponse
    {
        $wedding = $this->current->get();

        $wedding->website()->updateOrCreate(
            ['wedding_id' => $wedding->id],
            $request->validated(),
        );

        return back()->with('status', 'website-saved');
    }
}
