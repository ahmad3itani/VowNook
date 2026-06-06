<?php

namespace App\Http\Controllers;

use App\Models\Wedding;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public, unauthenticated wedding website at /w/{slug}. Renders the
 * couple's published content; if nothing is published yet it still shows a
 * graceful hub with links to RSVP and the seat finder.
 */
class PublicWebsiteController extends Controller
{
    public function show(Wedding $wedding): Response
    {
        $website = $wedding->website;
        $published = (bool) ($website?->is_published ?? false);

        return Inertia::render('public/website', [
            'wedding' => [
                'name' => $wedding->name,
                'slug' => $wedding->slug,
                'event_date' => $wedding->event_date?->toIso8601String(),
            ],
            'published' => $published,
            'content' => $published ? [
                'headline' => $website->headline,
                'welcome_message' => $website->welcome_message,
                'our_story' => $website->our_story,
                'venue_name' => $website->venue_name,
                'venue_address' => $website->venue_address,
                'ceremony_time' => $website->ceremony_time,
                'dress_code' => $website->dress_code,
                'hero_image_url' => $website->hero_image_url,
            ] : null,
        ]);
    }
}
