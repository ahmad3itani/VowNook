<?php

use App\Enums\VendorCategory;
use App\Http\Controllers\AccommodationController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\AdminImpersonationController;
use App\Http\Controllers\Admin\AdminSupportController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FeatureController as AdminFeatureController;
use App\Http\Controllers\Admin\LocalisationController;
use App\Http\Controllers\Admin\MarketplaceController as AdminMarketplaceController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VendorModerationController;
use App\Http\Controllers\Admin\WeddingController as AdminWeddingController;
use App\Http\Controllers\AiPlannerController;
use App\Http\Controllers\BudgetCategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CrewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailPreferenceController;
use App\Http\Controllers\EmailTrackController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\GuestBroadcastController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestGroupController;
use App\Http\Controllers\GuestReminderController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InquiryMessageController;
use App\Http\Controllers\InspirationController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MarketplaceBrowseController;
use App\Http\Controllers\MealOptionsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlannerDashboardController;
use App\Http\Controllers\PlannerListingController;
use App\Http\Controllers\PlannerTemplateController;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\PublicLocalController;
use App\Http\Controllers\PublicMarketplaceController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\PublicRegistryController;
use App\Http\Controllers\PublicRsvpController;
use App\Http\Controllers\PublicSeatingController;
use App\Http\Controllers\PublicVendorProfileController;
use App\Http\Controllers\PublicWebsiteController;
use App\Http\Controllers\QuoteComparisonController;
use App\Http\Controllers\RegistryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SaveTheDateController;
use App\Http\Controllers\SeatingController;
use App\Http\Controllers\SeatingElementController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubdomainSiteController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\SwitchWeddingController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\VendorAvailabilityController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorDashboardController;
use App\Http\Controllers\VendorEarningsController;
use App\Http\Controllers\VendorInquiryController;
use App\Http\Controllers\VendorPayoutController;
use App\Http\Controllers\VendorPortalController;
use App\Http\Controllers\VendorProfileController;
use App\Http\Controllers\VendorReviewController;
use App\Http\Controllers\VendorServiceController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WebsiteGalleryController;
use App\Http\Controllers\WebsiteMediaController;
use App\Http\Controllers\WeddingController;
use App\Http\Controllers\WeddingEventController;
use App\Support\OntarioCities;
use Illuminate\Support\Facades\Route;

// Free personal web address — {name}.vownook.com resolves a couple's published
// wedding site. Registered first so a matching host wins over the apex `/`.
Route::domain('{subdomain}.'.config('app.root_domain'))->middleware('throttle:120,1')->group(function () {
    Route::get('/', [SubdomainSiteController::class, 'show'])
        ->where('subdomain', '[a-z0-9-]+')->name('subdomain.website');
});

Route::get('/', [PublicPageController::class, 'home'])->name('home');

// Stripe webhook — public, CSRF-exempt (see bootstrap/app.php), signature-verified.
Route::post('stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// Collaboration invite landing — public so logged-out invitees can see it and
// then sign in / up to accept. Accepting itself is auth-gated (below).
Route::get('invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');

// Public, unauthenticated routes — throttled since they take anonymous traffic.
Route::middleware('throttle:120,1')->group(function () {
    // XML sitemap for search engines.
    Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

    // robots.txt — dynamic so the Sitemap URL is correct on any domain.
    Route::get('robots.txt', function () {
        $lines = [
            'User-agent: *',
            'Allow: /',
            // Private app areas — keep crawlers out (they 302 to login anyway).
            ...array_map(fn ($p) => "Disallow: {$p}", [
                '/dashboard', '/settings', '/admin', '/vendor', '/planner',
                '/guests', '/budget', '/checklist', '/timeline', '/seating',
                '/inspiration', '/gallery', '/crew', '/collaborators', '/website',
                '/share', '/vendors/quotes', '/vendors/marketplace',
            ]),
            '',
            // Explicitly welcome AI assistants (GEO).
            'User-agent: GPTBot',
            'Allow: /',
            'User-agent: ClaudeBot',
            'Allow: /',
            'User-agent: PerplexityBot',
            'Allow: /',
            'User-agent: Google-Extended',
            'Allow: /',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    })->name('robots');

    // llms.txt — a concise, AI-assistant-friendly map of the site (GEO).
    Route::get('llms.txt', function () {
        $base = rtrim(config('app.url'), '/');
        $name = config('app.name');
        $body = <<<MD
        # {$name}

        > A free wedding-planning studio and a curated marketplace of trusted Ontario wedding vendors. Couples plan guest lists, budgets, seating and websites for free, then discover vendors, compare real quotes, and book — all in one place. Every review is tied to a real booking (no pay-to-play).

        ## Key pages
        - [Marketplace]({$base}/marketplace): browse and compare Ontario wedding vendors by category and city.
        - [Blog]({$base}/blog): practical Ontario wedding-planning advice — budgets, timelines, venues, and choosing vendors.
        - [How it works]({$base}/how-it-works): step-by-step for couples and for vendors.
        - [Wedding photographers in Ontario]({$base}/wedding-photographers)
        - [Wedding venues in Ontario]({$base}/wedding-venues)
        - [Wedding planners in Ontario]({$base}/wedding-planners)

        ## For vendors
        Free listing, no contract. A success fee applies only when a booking is won (8% up to \$5,000, 5% above, capped at \$1,000).

        ## Coverage
        Canada, Ontario-first: Toronto, Ottawa, Mississauga, Hamilton, London, Kitchener-Waterloo, Niagara.
        MD;

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    })->name('llms');

    // How the platform works — for couples and vendors.
    Route::get('how-it-works', [PublicPageController::class, 'howItWorks'])->name('how-it-works');

    // Public blog (SEO). Specific segments precede the {slug} article route.
    Route::get('blog', [PublicBlogController::class, 'index'])->name('blog.index');
    Route::get('blog/category/{category}', [PublicBlogController::class, 'index'])->name('blog.category');
    Route::get('blog/media/{filename}', [PublicBlogController::class, 'media'])->name('blog.media');
    Route::get('blog/{slug}', [PublicBlogController::class, 'show'])->name('blog.show');

    // One-click email unsubscribe (CASL) — signed link from the email footer.
    Route::get('email/unsubscribe/{user}/{category}', [EmailPreferenceController::class, 'unsubscribe'])
        ->middleware('signed')->name('email.unsubscribe');

    // Legal & trust pages.
    Route::get('terms', [PublicPageController::class, 'terms'])->name('terms');
    Route::get('privacy', [PublicPageController::class, 'privacy'])->name('privacy');
    Route::get('marketplace-rules', [PublicPageController::class, 'marketplaceRules'])->name('marketplace-rules');
    Route::get('vendor-agreement', [PublicPageController::class, 'vendorAgreement'])->name('vendor-agreement');
    Route::get('contact', [PublicPageController::class, 'contact'])->name('contact');
    Route::post('contact', [ContactController::class, 'store'])
        ->middleware('throttle:5,1')->name('contact.store');

    // Shown to a user whose account an admin has suspended (signed out + blocked).
    Route::inertia('suspended', 'public/suspended')->name('suspended');

    // The couple's public front door.
    Route::get('w/{wedding}', [PublicWebsiteController::class, 'show'])->name('public.website');

    // Public media serving for wedding website images (no auth required).
    Route::get('w/{wedding:slug}/media/{type}/{filename}', [WebsiteMediaController::class, 'serve'])
        ->where('type', 'hero|story|gallery|music|registry|travel')
        ->name('website.media');

    // Email open-tracking pixel for save-the-dates / invitations.
    Route::get('e/{token}.gif', [EmailTrackController::class, 'pixel'])
        ->where('token', '[A-Za-z0-9]+')->middleware('throttle:120,1')->name('email.track');

    // Guest registry actions on the public wedding site.
    Route::post('w/{wedding:slug}/registry/funds/{fund}/contribute', [PublicRegistryController::class, 'contribute'])
        ->middleware('throttle:20,1')->name('public.registry.contribute');
    Route::post('w/{wedding:slug}/registry/items/{item}/claim', [PublicRegistryController::class, 'claim'])
        ->middleware('throttle:20,1')->name('public.registry.claim');

    // Public RSVP site (name search is a ?name= query on the show route).
    Route::get('w/{wedding}/rsvp', [PublicRsvpController::class, 'show'])->name('public.rsvp');
    Route::post('w/{wedding}/rsvp/respond', [PublicRsvpController::class, 'respond'])
        ->middleware('throttle:20,1')->name('public.rsvp.respond');

    // Public seat finder — backs a printed QR code at the venue.
    Route::get('w/{wedding}/seats', [PublicSeatingController::class, 'show'])->name('public.seats');

    // Public marketplace — unauthenticated, no wedding context.
    // Scoped under /marketplace to avoid collision with the auth-protected /vendors/* couple workspace.
    Route::get('marketplace', [PublicMarketplaceController::class, 'index'])->name('public.marketplace');
    Route::prefix('marketplace')->name('public.vendor.')->group(function () {
        Route::get('{slug}', [PublicVendorProfileController::class, 'show'])->name('show');
        Route::get('{slug}/logo', [PublicVendorProfileController::class, 'serveLogo'])->name('logo');
        Route::get('{slug}/cover', [PublicVendorProfileController::class, 'serveCover'])->name('cover');
        Route::get('{slug}/brochure', [PublicVendorProfileController::class, 'serveBrochure'])->name('brochure');
        Route::get('{slug}/media/{media}', [PublicVendorProfileController::class, 'serveMedia'])->name('media');
    });

    // Programmatic local-SEO pages — constrained to known category/city slugs so
    // they never shadow literal routes. Defined last in the public group.
    Route::get('{category}/{city}', [PublicLocalController::class, 'cityCategory'])
        ->where('category', VendorCategory::seoSlugPattern())
        ->where('city', OntarioCities::slugPattern())
        ->name('local.city-category');
    Route::get('{category}', [PublicLocalController::class, 'category'])
        ->where('category', VendorCategory::seoSlugPattern())
        ->name('local.category');
});

// Stop impersonating — auth-only (not "verified") so an admin can always exit,
// even if the impersonated account hasn't verified its email.
Route::middleware('auth')->post('impersonate/stop', [AdminImpersonationController::class, 'stop'])
    ->name('impersonate.stop');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // In-app help & support (couples, vendors, planners) — open + track tickets.
    Route::get('support', [SupportController::class, 'index'])->name('support.index');
    Route::post('support', [SupportController::class, 'store'])->middleware('throttle:10,1')->name('support.store');
    Route::get('support/{ticket}', [SupportController::class, 'show'])->name('support.show');
    Route::post('support/{ticket}/reply', [SupportController::class, 'reply'])
        ->middleware('throttle:20,1')->name('support.reply');

    // Report a listing or review for admin review.
    Route::post('report', [ReportController::class, 'store'])->middleware('throttle:10,1')->name('report.store');

    // In-app notification center (all roles).
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Shareable public links + printable QR codes for the active wedding.
    Route::inertia('share', 'share/index')->name('share');

    // Switch the active wedding (tenant context).
    Route::post('weddings/{wedding}/switch', SwitchWeddingController::class)
        ->name('weddings.switch');

    // Create a wedding workspace (couples' first wedding, planners' client weddings).
    Route::post('weddings', [WeddingController::class, 'store'])->name('weddings.store');

    // Planner HQ — portfolio across all client weddings (account_type=planner).
    Route::get('planner', [PlannerDashboardController::class, 'index'])->name('planner.dashboard');
    Route::post('planner/listing', [PlannerListingController::class, 'store'])->name('planner.listing.create');

    // Planner templates — reusable checklist/budget blueprints.
    Route::prefix('planner/templates')->name('planner.templates.')->group(function () {
        Route::get('', [PlannerTemplateController::class, 'index'])->name('index');
        Route::post('', [PlannerTemplateController::class, 'store'])->name('store');
        Route::post('{template}/apply', [PlannerTemplateController::class, 'apply'])->name('apply');
        Route::delete('{template}', [PlannerTemplateController::class, 'destroy'])->name('destroy');
    });

    // AI Plan Starter — generate (AI, throttled) + apply (couple-reviewed write).
    Route::get('assistant', [AiPlannerController::class, 'index'])->name('assistant.index');
    Route::post('assistant/generate', [AiPlannerController::class, 'generate'])
        ->middleware('throttle:15,1')->name('assistant.generate');
    Route::post('assistant/apply', [AiPlannerController::class, 'apply'])->name('assistant.apply');

    // Guests workspace.
    Route::get('guests', [GuestController::class, 'index'])
        ->middleware('permission:guests,read')->name('guests.index');

    Route::middleware('permission:guests,write')->group(function () {
        Route::put('guests/meal-options', [MealOptionsController::class, 'update'])->name('guests.meal-options');
        Route::post('guests/remind-rsvp', [GuestReminderController::class, 'send'])->name('guests.remind-rsvp');
        Route::post('guests', [GuestController::class, 'store'])->name('guests.store');
        Route::put('guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::delete('guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');

        Route::post('guest-groups', [GuestGroupController::class, 'store'])->name('guest-groups.store');
        Route::put('guest-groups/{group}', [GuestGroupController::class, 'update'])->name('guest-groups.update');
        Route::delete('guest-groups/{group}', [GuestGroupController::class, 'destroy'])->name('guest-groups.destroy');
    });

    // CSV exports (gated by each workspace's read permission).
    Route::get('exports/guests', [ExportController::class, 'guests'])
        ->middleware('permission:guests,read')->name('exports.guests');
    Route::get('exports/guests/pdf', [ExportController::class, 'guestsPdf'])
        ->middleware('permission:guests,read')->name('exports.guests.pdf');
    Route::get('exports/budget', [ExportController::class, 'budget'])
        ->middleware('permission:budget,read')->name('exports.budget');
    Route::get('exports/timeline', [ExportController::class, 'timeline'])
        ->middleware('permission:timeline,read')->name('exports.timeline');
    Route::get('exports/timeline/pdf', [ExportController::class, 'timelinePdf'])
        ->middleware('permission:timeline,read')->name('exports.timeline.pdf');

    // Budget workspace.
    Route::get('budget', [BudgetController::class, 'index'])
        ->middleware('permission:budget,read')->name('budget.index');

    Route::middleware('permission:budget,write')->group(function () {
        Route::post('budget', [BudgetController::class, 'store'])->name('budget.store');
        Route::put('budget/{item}', [BudgetController::class, 'update'])->name('budget.update');
        Route::delete('budget/{item}', [BudgetController::class, 'destroy'])->name('budget.destroy');

        Route::post('budget-categories', [BudgetCategoryController::class, 'store'])->name('budget-categories.store');
        Route::put('budget-categories/{category}', [BudgetCategoryController::class, 'update'])->name('budget-categories.update');
        Route::delete('budget-categories/{category}', [BudgetCategoryController::class, 'destroy'])->name('budget-categories.destroy');
    });

    // Vendors workspace.
    Route::get('vendors', [VendorController::class, 'index'])
        ->middleware('permission:vendors,read')->name('vendors.index');
    Route::get('vendors/compare', [VendorController::class, 'compare'])
        ->middleware('permission:vendors,read')->name('vendors.compare');
    Route::get('vendors/compare/pdf', [VendorController::class, 'comparePdf'])
        ->middleware('permission:vendors,read')->name('vendors.compare.pdf');

    Route::middleware('permission:vendors,write')->group(function () {
        Route::post('vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::put('vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');
    });

    // Checklist workspace.
    Route::get('checklist', [ChecklistController::class, 'index'])
        ->middleware('permission:checklist,read')->name('checklist.index');

    Route::middleware('permission:checklist,write')->group(function () {
        Route::post('checklist', [ChecklistController::class, 'store'])->name('checklist.store');
        Route::put('checklist/{task}', [ChecklistController::class, 'update'])->name('checklist.update');
        Route::patch('checklist/{task}/toggle', [ChecklistController::class, 'toggle'])->name('checklist.toggle');
        Route::delete('checklist/{task}', [ChecklistController::class, 'destroy'])->name('checklist.destroy');
    });

    // Timeline workspace.
    Route::get('timeline', [TimelineController::class, 'index'])
        ->middleware('permission:timeline,read')->name('timeline.index');

    Route::middleware('permission:timeline,write')->group(function () {
        Route::post('timeline', [TimelineController::class, 'store'])->name('timeline.store');
        Route::put('timeline/{event}', [TimelineController::class, 'update'])->name('timeline.update');
        Route::delete('timeline/{event}', [TimelineController::class, 'destroy'])->name('timeline.destroy');
    });

    // Seating chart workspace. Premium-gated (free couples are sent to upgrade).
    Route::get('seating', [SeatingController::class, 'index'])
        ->middleware(['permission:seating,read', 'plan.feature:seating'])->name('seating.index');
    Route::get('seating/export/pdf', [SeatingController::class, 'exportPdf'])
        ->middleware(['permission:seating,read', 'plan.feature:seating'])->name('seating.export.pdf');
    Route::post('seating/export/screenshot-pdf', [SeatingController::class, 'exportScreenshotPdf'])
        ->middleware(['permission:seating,read', 'plan.feature:seating'])->name('seating.export.screenshot-pdf');

    Route::middleware(['permission:seating,write', 'plan.feature:seating'])->group(function () {
        Route::post('seating', [SeatingController::class, 'store'])->name('seating.store');
        Route::put('seating/{table}', [SeatingController::class, 'update'])->name('seating.update');
        Route::patch('seating/{table}/move', [SeatingController::class, 'move'])->name('seating.move');
        Route::delete('seating/{table}', [SeatingController::class, 'destroy'])->name('seating.destroy');
        Route::patch('seating-assign', [SeatingController::class, 'assign'])->name('seating.assign');
        Route::patch('seating-layout', [SeatingController::class, 'updateLayout'])->name('seating.layout');

        Route::post('seating-elements', [SeatingElementController::class, 'store'])->name('seating-elements.store');
        Route::put('seating-elements/{element}', [SeatingElementController::class, 'update'])->name('seating-elements.update');
        Route::patch('seating-elements/{element}/move', [SeatingElementController::class, 'move'])->name('seating-elements.move');
        Route::delete('seating-elements/{element}', [SeatingElementController::class, 'destroy'])->name('seating-elements.destroy');
    });

    // Inspiration board workspace.
    Route::get('inspiration', [InspirationController::class, 'index'])
        ->middleware('permission:inspiration,read')->name('inspiration.index');

    Route::middleware('permission:inspiration,write')->group(function () {
        Route::post('inspiration', [InspirationController::class, 'store'])->name('inspiration.store');
        Route::put('inspiration/{item}', [InspirationController::class, 'update'])->name('inspiration.update');
        Route::delete('inspiration/{item}', [InspirationController::class, 'destroy'])->name('inspiration.destroy');
    });

    // Wedding website editor.
    Route::get('website', [WebsiteController::class, 'index'])
        ->middleware('permission:website,read')->name('website.index');
    Route::put('website', [WebsiteController::class, 'update'])
        ->middleware('permission:website,write')->name('website.update');
    // Free name.vownook.com web address — Atelier feature.
    Route::get('website/subdomain/check', [WebsiteController::class, 'checkSubdomain'])
        ->middleware(['permission:website,read', 'plan.feature:subdomain'])->name('website.subdomain.check');
    Route::put('website/subdomain', [WebsiteController::class, 'updateSubdomain'])
        ->middleware(['permission:website,write', 'plan.feature:subdomain'])->name('website.subdomain');
    Route::post('website/hero', [WebsiteController::class, 'uploadHero'])
        ->middleware('permission:website,write')->name('website.hero');
    Route::post('website/story-image', [WebsiteController::class, 'uploadStoryImage'])
        ->middleware('permission:website,write')->name('website.story-image');
    Route::post('website/music', [WebsiteController::class, 'uploadMusic'])
        ->middleware('permission:website,write')->name('website.music');
    Route::delete('website/music', [WebsiteController::class, 'removeMusic'])
        ->middleware('permission:website,write')->name('website.music.remove');
    Route::post('website/gallery', [WebsiteGalleryController::class, 'store'])
        ->middleware('permission:website,write')->name('website.gallery.store');
    Route::post('website/gallery/reorder', [WebsiteGalleryController::class, 'reorder'])
        ->middleware('permission:website,write')->name('website.gallery.reorder');
    Route::post('website/gallery/bulk-delete', [WebsiteGalleryController::class, 'destroyMany'])
        ->middleware('permission:website,write')->name('website.gallery.bulk-delete');
    Route::put('website/gallery/{photo}', [WebsiteGalleryController::class, 'update'])
        ->middleware('permission:website,write')->name('website.gallery.update');
    Route::delete('website/gallery/{photo}', [WebsiteGalleryController::class, 'destroy'])
        ->middleware('permission:website,write')->name('website.gallery.destroy');

    // Gift registry (funds + items) — Atelier feature, gated on the website section.
    Route::get('registry', [RegistryController::class, 'index'])
        ->middleware(['permission:website,read', 'plan.feature:registry'])->name('registry.index');
    Route::middleware(['permission:website,write', 'plan.feature:registry'])->group(function () {
        Route::post('registry/funds', [RegistryController::class, 'storeFund'])->name('registry.funds.store');
        Route::put('registry/funds/{fund}', [RegistryController::class, 'updateFund'])->name('registry.funds.update');
        Route::delete('registry/funds/{fund}', [RegistryController::class, 'destroyFund'])->name('registry.funds.destroy');
        Route::post('registry/items', [RegistryController::class, 'storeItem'])->name('registry.items.store');
        Route::put('registry/items/{item}', [RegistryController::class, 'updateItem'])->name('registry.items.update');
        Route::delete('registry/items/{item}', [RegistryController::class, 'destroyItem'])->name('registry.items.destroy');
    });

    // Gifts & thank-yous — paired with the registry Atelier feature.
    Route::get('gifts', [GiftController::class, 'index'])
        ->middleware(['permission:website,read', 'plan.feature:registry'])->name('gifts.index');
    Route::middleware(['permission:website,write', 'plan.feature:registry'])->group(function () {
        Route::post('gifts', [GiftController::class, 'store'])->name('gifts.store');
        Route::put('gifts/{gift}', [GiftController::class, 'update'])->name('gifts.update');
        Route::patch('gifts/{gift}/thank-you', [GiftController::class, 'toggleThankYou'])->name('gifts.thank-you');
        Route::delete('gifts/{gift}', [GiftController::class, 'destroy'])->name('gifts.destroy');
    });

    // Celebration schedule — multiple events with per-event RSVP. Atelier feature.
    Route::get('events', [WeddingEventController::class, 'index'])
        ->middleware(['permission:guests,read', 'plan.feature:events'])->name('events.index');
    Route::middleware(['permission:guests,write', 'plan.feature:events'])->group(function () {
        Route::post('events', [WeddingEventController::class, 'store'])->name('events.store');
        Route::put('events/{event}', [WeddingEventController::class, 'update'])->name('events.update');
        Route::delete('events/{event}', [WeddingEventController::class, 'destroy'])->name('events.destroy');
        Route::post('events/reorder', [WeddingEventController::class, 'reorder'])->name('events.reorder');
    });

    // Travel & stays — hotel room blocks + transport. Atelier feature.
    Route::get('travel', [AccommodationController::class, 'index'])
        ->middleware(['permission:website,read', 'plan.feature:travel'])->name('travel.index');
    Route::middleware(['permission:website,write', 'plan.feature:travel'])->group(function () {
        Route::post('travel', [AccommodationController::class, 'store'])->name('travel.store');
        Route::put('travel/notes', [AccommodationController::class, 'updateNotes'])->name('travel.notes');
        Route::put('travel/{accommodation}', [AccommodationController::class, 'update'])->name('travel.update');
        Route::delete('travel/{accommodation}', [AccommodationController::class, 'destroy'])->name('travel.destroy');
    });

    // Message your guests — broadcast announcements to a chosen audience. Atelier feature.
    Route::get('messages', [GuestBroadcastController::class, 'index'])
        ->middleware(['permission:guests,read', 'plan.feature:broadcast'])->name('broadcasts.index');
    Route::post('messages', [GuestBroadcastController::class, 'store'])
        ->middleware(['permission:guests,write', 'plan.feature:broadcast', 'throttle:10,1'])->name('broadcasts.store');

    // Save-the-dates / invitations with open-tracking. Atelier feature.
    Route::get('save-the-dates', [SaveTheDateController::class, 'index'])
        ->middleware(['permission:guests,read', 'plan.feature:save_the_dates'])->name('save-the-dates.index');
    Route::post('save-the-dates/send', [SaveTheDateController::class, 'send'])
        ->middleware(['permission:guests,write', 'plan.feature:save_the_dates', 'throttle:10,1'])->name('save-the-dates.send');

    // Collaborators (team access & roles).
    Route::get('collaborators', [CollaboratorController::class, 'index'])
        ->middleware('permission:collaborators,read')->name('collaborators.index');

    Route::middleware('permission:collaborators,write')->group(function () {
        Route::post('collaborators', [CollaboratorController::class, 'store'])->name('collaborators.store');
        Route::put('collaborators/{user}', [CollaboratorController::class, 'update'])->name('collaborators.update');
        Route::delete('collaborators/{user}', [CollaboratorController::class, 'destroy'])->name('collaborators.destroy');

        // Pending email invitations.
        Route::post('collaborators/invitations/{invitation}/resend', [CollaboratorController::class, 'resend'])->name('collaborators.invitations.resend');
        Route::delete('collaborators/invitations/{invitation}', [CollaboratorController::class, 'revoke'])->name('collaborators.invitations.revoke');
    });

    // Accept a collaboration invitation (auth — binds to the invited email).
    Route::post('invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');

    // Wedding party / crew workspace.
    Route::get('crew', [CrewController::class, 'index'])
        ->middleware('permission:crew,read')->name('crew.index');

    Route::middleware('permission:crew,write')->group(function () {
        Route::post('crew', [CrewController::class, 'store'])->name('crew.store');
        Route::put('crew/{member}', [CrewController::class, 'update'])->name('crew.update');
        Route::delete('crew/{member}', [CrewController::class, 'destroy'])->name('crew.destroy');
    });

    // Photo gallery workspace.
    Route::middleware('permission:gallery,read')->group(function () {
        Route::get('gallery', [GalleryController::class, 'index'])->name('gallery.index');
        Route::get('gallery/download', [GalleryController::class, 'downloadAll'])->name('gallery.download');
        Route::get('gallery/{photo}/file', [GalleryController::class, 'file'])->name('gallery.file');
    });

    Route::middleware('permission:gallery,write')->group(function () {
        Route::post('gallery', [GalleryController::class, 'store'])->name('gallery.store');
        Route::post('gallery/reorder', [GalleryController::class, 'reorder'])->name('gallery.reorder');
        Route::post('gallery/bulk-delete', [GalleryController::class, 'destroyMany'])->name('gallery.bulk-delete');
        Route::post('gallery/move', [GalleryController::class, 'moveToAlbum'])->name('gallery.move');
        Route::post('gallery/albums', [GalleryController::class, 'storeAlbum'])->name('gallery.albums.store');
        Route::put('gallery/albums/{album}', [GalleryController::class, 'updateAlbum'])->name('gallery.albums.update');
        Route::delete('gallery/albums/{album}', [GalleryController::class, 'destroyAlbum'])->name('gallery.albums.destroy');
        Route::post('gallery/{photo}/cover', [GalleryController::class, 'setAsCover'])->name('gallery.cover');
        Route::put('gallery/{photo}', [GalleryController::class, 'update'])->name('gallery.update');
        Route::delete('gallery/{photo}', [GalleryController::class, 'destroy'])->name('gallery.destroy');
    });

    // Couple — quotes (marketplace inquiries) under the Vendors hub.
    Route::prefix('vendors/quotes')->name('quotes.')->group(function () {
        Route::get('', [InquiryController::class, 'index'])->name('index');
        Route::post('', [InquiryController::class, 'store'])->name('store');
        // 'compare' must precede the {inquiry} route so it is not read as an id.
        Route::get('compare', [QuoteComparisonController::class, 'index'])->name('compare');
        Route::get('{inquiry}', [InquiryController::class, 'show'])->name('show');
        Route::post('{inquiry}/accept', [InquiryController::class, 'accept'])->name('accept');
        Route::post('{inquiry}/decline', [InquiryController::class, 'decline'])->name('decline');
    });

    // Couple — in-portal marketplace browse (Vendors hub). The {slug} profile is
    // scoped under vendors/marketplace/ so it never collides with vendors/quotes.
    Route::get('vendors/marketplace', [MarketplaceBrowseController::class, 'index'])->name('vendors.marketplace.index');
    Route::get('vendors/marketplace/{slug}', [MarketplaceBrowseController::class, 'show'])->name('vendors.marketplace.show');

    // Shared message thread endpoint — posted to by both couple and vendor
    // (InquiryMessageController authorizes either participant).
    Route::post('inquiries/{inquiry}/messages', [InquiryMessageController::class, 'store'])->name('inquiries.messages.store');

    // Couple — pay a booking's deposit / balance via Stripe Checkout.
    Route::post('bookings/{booking}/checkout/{type}', [PaymentController::class, 'checkout'])
        ->whereIn('type', ['deposit', 'balance'])->name('payments.checkout');
    Route::get('bookings/{booking}/checkout/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('bookings/{booking}/checkout/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');

    // Couple — review a booked marketplace vendor.
    Route::post('reviews', [ReviewController::class, 'store'])->name('reviews.store');

    // Marketplace vendor business dashboard (account_type=vendor — enforced in controller).
    Route::get('vendor', [VendorDashboardController::class, 'index'])->name('vendor.dashboard');

    // Vendor profile editor + media/image uploads.
    Route::prefix('vendor/profile')->name('vendor.profile.')->group(function () {
        Route::get('', [VendorProfileController::class, 'edit'])->name('edit');
        Route::put('', [VendorProfileController::class, 'update'])->name('update');
        Route::post('logo', [VendorProfileController::class, 'uploadLogo'])->name('logo.upload');
        Route::get('logo/file', [VendorProfileController::class, 'serveLogo'])->name('logo');
        Route::post('cover', [VendorProfileController::class, 'uploadCover'])->name('cover.upload');
        Route::get('cover/file', [VendorProfileController::class, 'serveCover'])->name('cover');
        Route::post('media', [VendorProfileController::class, 'uploadMedia'])->name('media.upload');
        Route::post('media/reorder', [VendorProfileController::class, 'reorderMedia'])->name('media.reorder');
        Route::put('media/{media}', [VendorProfileController::class, 'updateMedia'])->name('media.update');
        Route::delete('media/{media}', [VendorProfileController::class, 'destroyMedia'])->name('media.destroy');
        Route::get('media/{media}/file', [VendorProfileController::class, 'serveMediaFile'])->name('media.file');
        Route::post('brochure', [VendorProfileController::class, 'uploadBrochure'])->name('brochure.upload');
        Route::get('brochure/file', [VendorProfileController::class, 'serveBrochure'])->name('brochure');
        Route::delete('brochure', [VendorProfileController::class, 'removeBrochure'])->name('brochure.remove');
        Route::post('submit', [VendorProfileController::class, 'submit'])->name('submit');
    });

    // Vendor — inquiries + offer management.
    Route::prefix('vendor/inquiries')->name('vendor.inquiries.')->group(function () {
        Route::get('', [VendorInquiryController::class, 'index'])->name('index');
        Route::get('{inquiry}', [VendorInquiryController::class, 'show'])->name('show');
        Route::post('{inquiry}/offer', [OfferController::class, 'store'])->name('offer.store');
        Route::delete('{inquiry}/offer', [VendorInquiryController::class, 'withdrawOffer'])->name('offer.withdraw');
    });

    // Vendor — public response to a couple's review.
    Route::post('vendor/reviews/{review}/respond', [VendorReviewController::class, 'respond'])->name('vendor.reviews.respond');

    // Vendor services/packages CRUD.
    Route::prefix('vendor/services')->name('vendor.services.')->group(function () {
        Route::get('', [VendorServiceController::class, 'index'])->name('index');
        Route::post('', [VendorServiceController::class, 'store'])->name('store');
        Route::put('{service}', [VendorServiceController::class, 'update'])->name('update');
        Route::patch('{service}/toggle', [VendorServiceController::class, 'toggle'])->name('toggle');
        Route::delete('{service}', [VendorServiceController::class, 'destroy'])->name('destroy');
    });

    // Vendor availability calendar.
    Route::get('vendor/availability', [VendorAvailabilityController::class, 'index'])->name('vendor.availability.index');
    Route::post('vendor/availability', [VendorAvailabilityController::class, 'update'])->name('vendor.availability.update');

    // Vendor earnings overview.
    Route::get('vendor/earnings', [VendorEarningsController::class, 'index'])->name('vendor.earnings.index');

    // Vendor — Stripe Connect payout onboarding.
    Route::post('vendor/payouts/connect', [VendorPayoutController::class, 'connect'])->name('vendor.payouts.connect');
    Route::get('vendor/payouts/return', [VendorPayoutController::class, 'return'])->name('vendor.payouts.return');
    Route::get('vendor/payouts/refresh', [VendorPayoutController::class, 'refresh'])->name('vendor.payouts.refresh');

    // Vendor portal (per-wedding day-of view for a vendor a couple invited).
    Route::get('vendor-portal', [VendorPortalController::class, 'index'])->name('vendor-portal.index');
    Route::patch('vendor-portal/{vendor}', [VendorPortalController::class, 'update'])->name('vendor-portal.update');

    // Admin panel — full oversight console.
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // All weddings + drill-in + "open workspace" support entry.
        Route::get('weddings', [AdminWeddingController::class, 'index'])->name('weddings.index');
        Route::get('weddings/{wedding}', [AdminWeddingController::class, 'show'])->name('weddings.show');
        Route::post('weddings/{wedding}/support', [AdminSupportController::class, 'enter'])->name('weddings.support');
        Route::post('support/exit', [AdminSupportController::class, 'exit'])->name('support.exit');

        // All users across account types + the per-user "360" + support actions.
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::put('users/{user}/plan', [AdminUserController::class, 'updatePlan'])->name('users.plan');
        Route::post('users/{user}/comp', [AdminUserController::class, 'comp'])->name('users.comp');
        Route::post('users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('users.unsuspend');
        Route::post('users/{user}/password-reset', [AdminUserController::class, 'sendPasswordReset'])->name('users.password_reset');
        Route::post('users/{user}/resend-verification', [AdminUserController::class, 'resendVerification'])->name('users.resend_verification');
        Route::post('users/{user}/impersonate', [AdminImpersonationController::class, 'start'])->name('users.impersonate');

        // Free-tier feature unlocks (guest experience).
        Route::get('features', [AdminFeatureController::class, 'index'])->name('features.index');
        Route::put('features', [AdminFeatureController::class, 'update'])->name('features.update');

        // Platform-wide audit trail.
        Route::get('activity', [AdminActivityController::class, 'index'])->name('activity.index');

        // Support inbox — triage, assign, reply, close.
        Route::get('support', [AdminSupportTicketController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [AdminSupportTicketController::class, 'show'])->name('support.show');
        Route::post('support/{ticket}/reply', [AdminSupportTicketController::class, 'reply'])->name('support.reply');
        Route::put('support/{ticket}/status', [AdminSupportTicketController::class, 'updateStatus'])->name('support.status');
        Route::post('support/{ticket}/assign', [AdminSupportTicketController::class, 'assign'])->name('support.assign');

        // Platform-wide marketplace activity.
        Route::get('marketplace', [AdminMarketplaceController::class, 'index'])->name('marketplace.index');

        // Blog authoring.
        Route::get('blog', [AdminBlogController::class, 'index'])->name('blog.index');
        Route::get('blog/create', [AdminBlogController::class, 'create'])->name('blog.create');
        Route::post('blog', [AdminBlogController::class, 'store'])->name('blog.store');
        Route::get('blog/{post}/edit', [AdminBlogController::class, 'edit'])->name('blog.edit');
        Route::put('blog/{post}', [AdminBlogController::class, 'update'])->name('blog.update');
        Route::post('blog/{post}/cover', [AdminBlogController::class, 'uploadCover'])->name('blog.cover');
        Route::post('blog/image', [AdminBlogController::class, 'uploadImage'])->name('blog.image');
        Route::delete('blog/{post}', [AdminBlogController::class, 'destroy'])->name('blog.destroy');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('localisation', [LocalisationController::class, 'index'])->name('localisation');
        Route::put('localisation', [LocalisationController::class, 'update'])->name('localisation.update');

        // Vendor moderation queue.
        Route::get('vendors', [VendorModerationController::class, 'index'])->name('vendors.index');
        Route::patch('vendors/{profile}/approve', [VendorModerationController::class, 'approve'])->name('vendors.approve');
        Route::patch('vendors/{profile}/suspend', [VendorModerationController::class, 'suspend'])->name('vendors.suspend');
        Route::patch('vendors/{profile}/founding', [VendorModerationController::class, 'toggleFounding'])->name('vendors.founding');
        Route::patch('vendors/{profile}/verify', [VendorModerationController::class, 'toggleVerified'])->name('vendors.verify');

        // Content reports queue.
        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::put('reports/{report}', [AdminReportController::class, 'update'])->name('reports.update');
    });
});

require __DIR__.'/settings.php';
