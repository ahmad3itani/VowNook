<?php

namespace App\Http\Controllers;

use App\Models\TimelineEvent;
use App\Models\Wedding;
use App\Models\WeddingAccommodation;
use App\Models\WeddingEvent;
use App\Support\Seo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicWebsiteController extends Controller
{
    public function show(Wedding $wedding): Response
    {
        $website = $wedding->website;
        $published = (bool) ($website?->is_published ?? false);

        $content = null;

        if ($website !== null) {
            $photos = $website->photos()->get();

            $content = [
                'template'           => $website->template ?? 'classic',
                'headline'           => $website->headline,
                'welcome_message'    => $website->welcome_message,
                'our_story'          => $website->our_story,
                'venue_name'         => $website->venue_name,
                'venue_address'      => $website->venue_address,
                'ceremony_time'      => $website->ceremony_time,
                'dress_code'         => $website->dress_code,
                'hero_image_url'     => $website->hero_image_url,
                'hero_image_preview' => $website->hero_image_path && Storage::exists($website->hero_image_path)
                    ? route('website.media', [$wedding->slug, 'hero', basename($website->hero_image_path)])
                    : null,
                'hero_video_url'     => $website->hero_video_url,
                'story_image_preview' => $website->story_image_path && Storage::exists($website->story_image_path)
                    ? route('website.media', [$wedding->slug, 'story', basename($website->story_image_path)])
                    : null,
                'timeline_items'     => $website->timeline_items ?? [],
                'video_url'          => $website->video_url,
                'music_title'        => $website->music_title,
                'music_url'          => $website->music_path && Storage::exists($website->music_path)
                    ? route('website.media', [$wedding->slug, 'music', basename($website->music_path)])
                    : null,
                'photos'             => $photos->map(fn ($p) => [
                    'id'       => $p->id,
                    'url'      => route('website.media', [$wedding->slug, 'gallery', basename($p->path)]),
                    'caption'  => $p->caption,
                    'sort_order' => $p->sort_order,
                ])->values(),
            ];
        }

        $heroImage = $website && $website->hero_image_path && Storage::exists($website->hero_image_path)
            ? route('website.media', [$wedding->slug, 'hero', basename($website->hero_image_path)])
            : null;

        $description = $website?->welcome_message
            ? Str::limit(strip_tags($website->welcome_message), 155)
            : "You're invited to celebrate with {$wedding->name}"
                .($wedding->event_date ? ' on '.$wedding->event_date->format('F j, Y') : '').'.';

        $seo = Seo::make(
            title: $wedding->name,
            description: $description,
            canonical: route('public.website', $wedding->slug),
            image: $heroImage,
            // Only published sites are indexable; unpublished drafts are noindex.
            index: $published,
        );

        // The day-of schedule ("Order of the Day") — public-safe fields only
        // (no private notes / vendor details), shown only on a published site.
        $schedule = [];

        if ($published) {
            $schedule = TimelineEvent::query()
                ->where('wedding_id', $wedding->id)
                ->orderBy('starts_at')
                ->get(['title', 'type', 'starts_at', 'location'])
                ->map(fn (TimelineEvent $e) => [
                    'title'    => $e->title,
                    'type'     => $e->type?->value,
                    'time'     => $e->starts_at?->format('g:i A'),
                    'location' => $e->location,
                ])
                ->values();
        }

        // Celebration schedule — the multi-event weekend (rehearsal, welcome,
        // ceremony, reception, brunch …). Distinct from the day-of "Order of the
        // Day" timeline above. Public only on a published site.
        $events = [];

        if ($published) {
            $events = WeddingEvent::forWedding($wedding->id)->ordered()->get()
                ->map(fn (WeddingEvent $e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'type' => $e->type,
                    'date' => $e->event_date?->translatedFormat('l, F j, Y'),
                    'start_time' => $e->start_time,
                    'end_time' => $e->end_time,
                    'venue_name' => $e->venue_name,
                    'address' => $e->address,
                    'dress_code' => $e->dress_code,
                    'description' => $e->description,
                    'is_rsvpable' => $e->is_rsvpable,
                ])->values();
        }

        // Travel & stays — hotel blocks / rentals / transport + free-text notes.
        $travel = ['notes' => null, 'stays' => []];

        if ($published) {
            $travel['notes'] = $website?->travel_notes;
            $travel['stays'] = WeddingAccommodation::forWedding($wedding->id)->where('is_active', true)->ordered()->get()
                ->map(fn (WeddingAccommodation $a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'type' => $a->type,
                    'address' => $a->address,
                    'blurb' => $a->blurb,
                    'booking_url' => $a->booking_url,
                    'block_code' => $a->block_code,
                    'price_note' => $a->price_note,
                    'distance_note' => $a->distance_note,
                    'image_url' => $a->image_path && Storage::exists($a->image_path)
                        ? route('website.media', [$wedding->slug, 'travel', basename($a->image_path)]) : null,
                ])->values();
        }

        // Gift registry — active funds + items, only on a published site.
        $registry = ['funds' => [], 'items' => []];

        if ($published) {
            $registry['funds'] = $wedding->registryFunds()->where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn ($f) => [
                    'id' => $f->id,
                    'title' => $f->title,
                    'blurb' => $f->blurb,
                    'type' => $f->type,
                    'goal_cents' => $f->goal_cents,
                    'raised_cents' => $f->raised_cents,
                    'payout_url' => $f->payout_url,
                    'image_url' => $f->image_path && Storage::exists($f->image_path)
                        ? route('website.media', [$wedding->slug, 'registry', basename($f->image_path)]) : null,
                ])->values();

            $registry['items'] = $wedding->registryItems()->orderBy('sort_order')->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'blurb' => $i->blurb,
                    'price_cents' => $i->price_cents,
                    'store_url' => $i->store_url,
                    'quantity' => $i->quantity,
                    'claimed_count' => $i->claimed_count,
                    'image_url' => $i->image_path && Storage::exists($i->image_path)
                        ? route('website.media', [$wedding->slug, 'registry', basename($i->image_path)]) : null,
                ])->values();
        }

        return Inertia::render('public/website', [
            'wedding' => [
                'name'       => $wedding->name,
                'slug'       => $wedding->slug,
                'event_date' => $wedding->event_date?->toIso8601String(),
            ],
            'published' => $published,
            'content'   => $content,
            'schedule'  => $schedule,
            'events'    => $events,
            'travel'    => $travel,
            'registry'  => $registry,
        ])->withViewData(['seo' => $seo]);
    }
}
