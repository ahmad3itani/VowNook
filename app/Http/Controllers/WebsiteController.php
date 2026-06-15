<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeddingWebsiteRequest;
use App\Models\Wedding;
use App\Models\WeddingWebsite;
use App\Support\CurrentWedding;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();
        $website = $wedding->website;

        return Inertia::render('website/index', [
            'website' => $this->serialize($wedding, $website),
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

        // Publishing the website is the qualifying action that rewards a referrer.
        if ($request->boolean('is_published')) {
            \App\Support\Referrals::rewardForActivation($wedding);
        }

        return back()->with('status', 'website-saved');
    }

    public function uploadHero(Request $request): RedirectResponse
    {
        $request->validate(['hero' => ['required', 'image', 'max:10240']]);

        $wedding = $this->current->get();
        $website = $wedding->website()->firstOrCreate(['wedding_id' => $wedding->id]);

        if ($website->hero_image_path) {
            Storage::delete($website->hero_image_path);
        }

        $path = ImageOptimizer::store($request->file('hero'), "websites/{$wedding->id}/hero", 2400);
        $website->update(['hero_image_path' => $path]);

        return back()->with('status', 'hero-uploaded');
    }

    public function uploadStoryImage(Request $request): RedirectResponse
    {
        $request->validate(['story_image' => ['required', 'image', 'max:10240']]);

        $wedding = $this->current->get();
        $website = $wedding->website()->firstOrCreate(['wedding_id' => $wedding->id]);

        if ($website->story_image_path) {
            Storage::delete($website->story_image_path);
        }

        $path = ImageOptimizer::store($request->file('story_image'), "websites/{$wedding->id}/story", 1600);
        $website->update(['story_image_path' => $path]);

        return back()->with('status', 'story-image-uploaded');
    }

    public function uploadMusic(Request $request): RedirectResponse
    {
        $request->validate([
            'music' => [
                'required', 'file',
                'mimetypes:audio/mpeg,audio/mp4,audio/aac,audio/ogg,audio/x-m4a,audio/wav,audio/x-wav',
                'max:10240', // 10 MB
            ],
        ]);

        $wedding = $this->current->get();
        $website = $wedding->website()->firstOrCreate(['wedding_id' => $wedding->id]);

        if ($website->music_path) {
            Storage::delete($website->music_path);
        }

        // Store the raw audio file (ImageOptimizer is image-only).
        $path = $request->file('music')->store("websites/{$wedding->id}/music");

        $title = $website->music_title
            ?: pathinfo($request->file('music')->getClientOriginalName(), PATHINFO_FILENAME);

        $website->update(['music_path' => $path, 'music_title' => $title]);

        return back()->with('status', 'music-uploaded');
    }

    public function removeMusic(): RedirectResponse
    {
        $wedding = $this->current->get();
        $website = $wedding->website;

        if ($website?->music_path) {
            Storage::delete($website->music_path);
        }

        $website?->update(['music_path' => null, 'music_title' => null]);

        return back()->with('status', 'music-removed');
    }

    // -------------------------------------------------------------------------

    private function serialize(Wedding $wedding, ?WeddingWebsite $website): array
    {
        $photos = $website?->photos()->get() ?? collect();

        return [
            'is_published'       => (bool) ($website?->is_published ?? false),
            'template'           => $website?->template ?? 'classic',
            'headline'           => $website?->headline,
            'welcome_message'    => $website?->welcome_message,
            'our_story'          => $website?->our_story,
            'venue_name'         => $website?->venue_name,
            'venue_address'      => $website?->venue_address,
            'ceremony_time'      => $website?->ceremony_time,
            'dress_code'         => $website?->dress_code,
            'hero_image_url'     => $website?->hero_image_url,
            'hero_image_path'    => $website?->hero_image_path,
            'hero_image_preview' => $website?->hero_image_path
                ? route('website.media', [$wedding->slug, 'hero', basename($website->hero_image_path)])
                : null,
            'hero_video_url'     => $website?->hero_video_url,
            'story_image_path'   => $website?->story_image_path,
            'story_image_preview' => $website?->story_image_path
                ? route('website.media', [$wedding->slug, 'story', basename($website->story_image_path)])
                : null,
            'timeline_items'     => $website?->timeline_items ?? [],
            'video_url'          => $website?->video_url,
            'music_path'         => $website?->music_path,
            'music_title'        => $website?->music_title,
            'music_url'          => $website?->music_path
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
}
