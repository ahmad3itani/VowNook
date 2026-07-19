<?php

namespace App\Http\Controllers;

use App\Support\Budget\BudgetAllocator;
use App\Support\OntarioCities;
use App\Support\Seo;
use App\Support\Seo\LocalCosts;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Static marketing & legal pages, rendered with proper server-side SEO
 * (title, description, canonical, Open Graph) instead of the generic blade
 * fallback. The React pages no longer set their own <meta> — the head is
 * authoritative server-side here.
 */
class PublicPageController extends Controller
{
    /**
     * Featured metros for the homepage budget instrument. Deliberately the ten
     * with the most search demand rather than all 42 — the selector stays
     * scannable and the payload stays small.
     */
    private const HERO_CITIES = [
        'toronto', 'ottawa', 'mississauga', 'hamilton', 'niagara',
        'kitchener-waterloo', 'london', 'barrie', 'muskoka', 'windsor',
    ];

    public function home(): Response
    {
        return Inertia::render('welcome', [
            // Powers the hero instrument: the visitor gets a real allocation,
            // priced for their city, before they ever create an account.
            'budgetModel' => [
                ...BudgetAllocator::clientModel(),
                'bands' => BudgetAllocator::bands(),
                'cities' => $this->heroCities(),
            ],
        ])->withViewData(['seo' => Seo::make(
            title: 'Wedding Planning Studio & Ontario Vendor Marketplace',
            description: 'Bring your budget — see exactly what an Ontario wedding costs in your city, then plan it and book trusted vendors free.',
            canonical: url('/'),
            image: url('/images/landing/hero.jpg'),
        )]);
    }

    /**
     * @return list<array{slug:string, name:string, index:float, costs:list<array{noun:string, display:string}>}>
     */
    private function heroCities(): array
    {
        $allocator = new BudgetAllocator;
        $costs = new LocalCosts;

        $out = [];
        foreach (self::HERO_CITIES as $slug) {
            $name = OntarioCities::name($slug);

            if ($name === null) {
                continue;
            }

            $out[] = [
                'slug' => $slug,
                'name' => $name,
                'index' => $allocator->cityIndex($slug),
                // Real per-category ranges for this city, straight from the same
                // engine the /wedding-{category}/{city} pages use — so the hero
                // can never quote a number the rest of the site contradicts.
                'costs' => array_map(
                    fn (array $row) => ['noun' => $row['noun'], 'display' => $row['display']],
                    $costs->table($slug),
                ),
            ];
        }

        return $out;
    }

    public function howItWorks(): Response
    {
        $faqs = Seo::brandFaqs();

        return Inertia::render('public/how-it-works', [
            'faqs' => $faqs,
        ])->withViewData(['seo' => Seo::make(
            title: 'How It Works',
            description: 'How VowNook works for couples (plan, browse vendors, compare quotes and book — free) and for vendors (free listing, real inquiries, pay only when booked).',
            canonical: route('how-it-works'),
            // FAQPage schema mirrors the visible FAQ below — AI assistants lift it.
            schemas: [Seo::faqSchema($faqs)],
        )]);
    }

    public function features(): Response
    {
        return Inertia::render('public/features')->withViewData(['seo' => Seo::make(
            title: 'Features — Every Wedding Tool, One Calm Studio',
            description: 'A guided tour of every VowNook tool with real screenshots: dashboard, guest list & RSVPs, budget, checklist, seating studio, wedding website, registry, vendor marketplace and the stationery studio.',
            canonical: route('features'),
            image: url('/images/tour/dashboard.webp'),
        )]);
    }

    public function pricing(): Response
    {
        $faqs = [
            ['q' => 'Is Atelier really a one-time payment?', 'a' => 'Yes. Atelier is $99 once, per wedding — not a subscription. It covers you from today until (and after) your wedding day, with every feature and future upgrade included.'],
            ['q' => 'Do couples pay anything to book vendors?', 'a' => 'No. Browsing, requesting quotes, comparing offers and booking are all free for couples. Vendors pay a small success fee only when a booking actually happens.'],
            ['q' => 'What happens if I outgrow the free plan?', 'a' => 'Nothing is ever deleted. If you reach a free limit — like 25 guests — you can upgrade to Atelier in one click, or keep planning within the free limits. Your data is always safe and always yours.'],
            ['q' => 'What does a vendor listing cost?', 'a' => 'Nothing — no subscription, no contract. Vendors pay only a success fee when a couple books: 8% on the first $5,000 of a booking, 5% above that, capped at $1,000 per booking.'],
        ];

        return Inertia::render('public/pricing', [
            'faqs' => $faqs,
        ])->withViewData(['seo' => Seo::make(
            title: 'Pricing — Free Planning, One-Time Atelier Upgrade',
            description: 'VowNook pricing, honestly: couples plan free (Atelier is $99 once, not a subscription), planners get HQ at $499/yr, and vendors list free and pay only a capped success fee when booked.',
            canonical: route('pricing'),
            schemas: [Seo::faqSchema($faqs)],
        )]);
    }

    public function terms(): Response
    {
        return Inertia::render('public/terms')->withViewData(['seo' => Seo::make(
            title: 'Terms of Service',
            description: 'The terms that govern your use of VowNook — the wedding planning studio and Ontario vendor marketplace.',
            canonical: route('terms'),
        )]);
    }

    public function privacy(): Response
    {
        return Inertia::render('public/privacy')->withViewData(['seo' => Seo::make(
            title: 'Privacy Policy',
            description: "How VowNook collects, uses and protects personal information, in line with Canada's PIPEDA.",
            canonical: route('privacy'),
        )]);
    }

    public function contact(): Response
    {
        return Inertia::render('public/contact')->withViewData(['seo' => Seo::make(
            title: 'Contact',
            description: 'Questions about planning, vendor listings, privacy or partnerships — get in touch with the VowNook team.',
            canonical: route('contact'),
        )]);
    }

    public function marketplaceRules(): Response
    {
        return Inertia::render('public/marketplace-rules')->withViewData(['seo' => Seo::make(
            title: 'Marketplace Rules',
            description: 'The rules that keep the VowNook marketplace safe and trustworthy for couples and vendors — accurate listings, real reviews, no off-platform circumvention, and how we handle reports and fraud.',
            canonical: route('marketplace-rules'),
        )]);
    }

    public function vendorAgreement(): Response
    {
        return Inertia::render('public/vendor-agreement')->withViewData(['seo' => Seo::make(
            title: 'Vendor Agreement',
            description: 'The terms every VowNook vendor agrees to: accurate listings, owning your portfolio, honouring quotes, the success-fee model, prohibited conduct, and grounds for suspension.',
            canonical: route('vendor-agreement'),
        )]);
    }
}
