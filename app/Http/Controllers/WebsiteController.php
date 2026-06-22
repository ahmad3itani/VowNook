<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeddingWebsiteRequest;
use App\Models\GuestbookEntry;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingPartyMember;
use App\Models\WeddingWebsite;
use App\Support\Ai\AiService;
use App\Support\CurrentWedding;
use App\Support\ImageOptimizer;
use App\Support\Referrals;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    /** Subdomains we never let couples claim (infra + brand). */
    public const RESERVED_SUBDOMAINS = [
        'www', 'app', 'api', 'admin', 'mail', 'smtp', 'ftp', 'cdn', 'assets', 'static',
        'blog', 'help', 'support', 'status', 'dashboard', 'login', 'register', 'account',
        'billing', 'stripe', 'webhook', 'webhooks', 'vownook', 'mx', 'ns', 'ns1', 'ns2', 'email', 'e',
    ];

    public function __construct(protected CurrentWedding $current) {}

    public function index(Request $request): Response
    {
        $wedding = $this->current->get();
        $website = $wedding->website;

        return Inertia::render('website/index', [
            'website' => $this->serialize($wedding, $website),
            'public_url' => route('public.website', $wedding),
            'can_publish' => $this->canPublish($wedding, $request->user()),
            'subdomain_base' => config('app.root_domain'),
            'subdomain_enabled' => $wedding->owner?->canUseFeature('subdomain') ?? false,
            // AI-fill is a paid perk (and needs a configured key). Free couples
            // still get the full editor — they just write the copy themselves.
            'ai_enabled' => app(AiService::class)->isConfigured()
                && (($request->user()?->is_admin ?? false) || ($wedding->owner?->canUseFeature('ai') ?? false)),
            'party_sides' => WeddingPartyMember::SIDES,
        ]);
    }

    /** Claim or change the free name.vownook.com web address. */
    public function updateSubdomain(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();

        $data = $request->validate([
            'subdomain' => [
                'nullable', 'string', 'min:3', 'max:40',
                'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/',
                Rule::notIn(self::RESERVED_SUBDOMAINS),
                Rule::unique('wedding_websites', 'subdomain')
                    ->ignore($wedding->website?->id),
            ],
        ], [
            'subdomain.regex' => 'Use lowercase letters, numbers and hyphens only.',
            'subdomain.not_in' => 'That address is reserved — please choose another.',
            'subdomain.unique' => 'That address is already taken.',
        ]);

        $wedding->website()->updateOrCreate(
            ['wedding_id' => $wedding->id],
            ['subdomain' => $data['subdomain'] ?? null],
        );

        return back()->with('status', 'subdomain-saved');
    }

    /** Live availability check for the editor. */
    public function checkSubdomain(Request $request): JsonResponse
    {
        $wedding = $this->current->get();
        $value = strtolower(trim((string) $request->query('value', '')));

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $value) || strlen($value) < 3) {
            return response()->json(['available' => false, 'reason' => 'invalid']);
        }
        if (in_array($value, self::RESERVED_SUBDOMAINS, true)) {
            return response()->json(['available' => false, 'reason' => 'reserved']);
        }

        $taken = WeddingWebsite::where('subdomain', $value)
            ->where('wedding_id', '!=', $wedding->id)
            ->exists();

        return response()->json(['available' => ! $taken, 'reason' => $taken ? 'taken' : 'ok']);
    }

    public function update(WeddingWebsiteRequest $request): RedirectResponse
    {
        $wedding = $this->current->get();
        $data = $request->validated();

        // Going live with the website is an Atelier (paid) feature. Free couples
        // can build and preview, but the site stays a draft until they upgrade —
        // we coerce rather than reject so saving the rest of the page still works.
        if (! $this->canPublish($wedding, $request->user())) {
            $data['is_published'] = false;
        }

        $wedding->website()->updateOrCreate(
            ['wedding_id' => $wedding->id],
            $data,
        );

        // Publishing the website is the qualifying action that rewards a referrer.
        if (! empty($data['is_published'])) {
            Referrals::rewardForActivation($wedding);
        }

        return back()->with('status', 'website-saved');
    }

    /** Entitlement to publish: admins, or a wedding owner on a paid plan. */
    private function canPublish(Wedding $wedding, ?User $actor): bool
    {
        return ($actor?->is_admin ?? false)
            || ($wedding->owner?->canUseFeature('website_publish') ?? false);
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
            'is_published' => (bool) ($website?->is_published ?? false),
            'subdomain' => $website?->subdomain,
            'template' => $website?->template ?? 'classic',
            'headline' => $website?->headline,
            'welcome_message' => $website?->welcome_message,
            'our_story' => $website?->our_story,
            'venue_name' => $website?->venue_name,
            'venue_address' => $website?->venue_address,
            'ceremony_time' => $website?->ceremony_time,
            'dress_code' => $website?->dress_code,
            'hero_image_url' => $website?->hero_image_url,
            'hero_image_path' => $website?->hero_image_path,
            'hero_image_preview' => $website?->hero_image_path
                ? route('website.media', [$wedding->slug, 'hero', basename($website->hero_image_path)])
                : null,
            'hero_video_url' => $website?->hero_video_url,
            'story_image_path' => $website?->story_image_path,
            'story_image_preview' => $website?->story_image_path
                ? route('website.media', [$wedding->slug, 'story', basename($website->story_image_path)])
                : null,
            'timeline_items' => $website?->timeline_items ?? [],
            'video_url' => $website?->video_url,
            'music_path' => $website?->music_path,
            'music_title' => $website?->music_title,
            'music_url' => $website?->music_path
                ? route('website.media', [$wedding->slug, 'music', basename($website->music_path)])
                : null,
            'photos' => $photos->map(fn ($p) => [
                'id' => $p->id,
                'url' => route('website.media', [$wedding->slug, 'gallery', basename($p->path)]),
                'caption' => $p->caption,
                'sort_order' => $p->sort_order,
            ])->values(),
            'travel_notes' => $website?->travel_notes,
            'faq_items' => $website?->faq_items ?? [],
            'local_recommendations' => $website?->local_recommendations ?? [],
            'party' => WeddingPartyMember::forWedding($wedding->id)->ordered()->get()->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'role' => $m->role,
                'side' => $m->side,
                'bio' => $m->bio,
                'photo_url' => $m->photo_path
                    ? route('website.media', [$wedding->slug, 'party', basename($m->photo_path)])
                    : null,
                'sort_order' => $m->sort_order,
            ])->values(),
            'guestbook' => GuestbookEntry::forWedding($wedding->id)->latest()->get()->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'message' => $e->message,
                'approved' => $e->isApproved(),
                'created_at' => $e->created_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
