<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorService;
use App\Support\OntarioCities;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds the marketplace with realistic but ENTIRELY FICTIONAL sample vendors so
 * the browse/inquiry/quote flow can be demoed during the beta — no real
 * business is listed or misrepresented. Every demo account uses a
 * "demo.vownook.test" email domain so the whole set can be removed in one step
 * (`marketplace:demo --purge`) the moment real vendors come on board.
 *
 * Three+ vendors per category are placed in Toronto so the programmatic city
 * pages clear the >=3-vendor indexing gate.
 */
class SeedDemoVendors extends Command
{
    protected $signature = 'marketplace:demo {--purge : Remove all demo vendors instead of creating them}';

    protected $description = 'Create (or purge) fictional sample vendors across every category for beta demos.';

    private const DEMO_DOMAIN = '@demo.vownook.test';

    public function handle(): int
    {
        if ($this->option('purge')) {
            return $this->purge();
        }

        $count = 0;

        foreach ($this->blueprint() as $category => $cat) {
            foreach ($cat['vendors'] as $i => [$name, $citySlug, $price]) {
                $city = OntarioCities::get($citySlug);
                if ($city === null) {
                    continue;
                }

                $slug = Str::slug($name);
                $email = $slug.self::DEMO_DOMAIN;

                $user = User::updateOrCreate(
                    ['email' => $email],
                    ['name' => $name, 'password' => 'demo-not-loginable-'.$slug, 'account_type' => 'vendor'],
                );
                $user->forceFill(['email_verified_at' => now()])->save();

                $profile = VendorProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'business_name' => $name,
                        'category' => $category,
                        'tagline' => $cat['tagline'],
                        'description' => $this->describe($cat['blurb'], $city['name']),
                        'city' => $city['name'],
                        'region' => $city['region'],
                        'country' => 'CA',
                        'service_area' => $city['name'].' & area',
                        'base_price_cents' => $price,
                        'price_unit' => $cat['unit'],
                        'status' => 'published',
                        'is_accepting_bookings' => true,
                        'agreement_accepted_at' => now(),
                        // First in each category is a "founding" + verified demo
                        // so the badges have something to render.
                        'is_founding' => $i === 0,
                        'verified_at' => $i < 2 ? now() : null,
                    ],
                );

                // Rebuild this profile's services each run (idempotent).
                VendorService::where('vendor_profile_id', $profile->id)->delete();
                foreach ($cat['services'] as $s => [$sName, $priceType, $sPrice]) {
                    VendorService::create([
                        'vendor_profile_id' => $profile->id,
                        'name' => $sName,
                        'description' => 'A popular option for couples in '.$city['name'].'.',
                        'price_cents' => $sPrice,
                        'price_unit' => $cat['unit'],
                        'price_type' => $priceType,
                        'is_active' => true,
                        'sort_order' => $s,
                    ]);
                }

                $count++;
            }
        }

        $this->info("Seeded {$count} demo vendors across ".count($this->blueprint()).' categories.');
        $this->line('Remove them anytime with: php artisan marketplace:demo --purge');

        return self::SUCCESS;
    }

    private function purge(): int
    {
        $users = User::where('email', 'like', '%'.self::DEMO_DOMAIN)->get();

        DB::transaction(function () use ($users) {
            foreach ($users as $user) {
                // Services cascade via the vendor_profile_id FK.
                VendorProfile::where('user_id', $user->id)->get()->each->delete();
                $user->delete();
            }
        });

        $this->info("Purged {$users->count()} demo vendors.");

        return self::SUCCESS;
    }

    private function describe(string $blurb, string $city): string
    {
        return $blurb.' Proudly serving couples across '.$city.' and the surrounding area.';
    }

    /**
     * @return array<string, array{unit:string, tagline:string, blurb:string, services:array<int, array{0:string,1:string,2:int}>, vendors:array<int, array{0:string,1:string,2:int}>}>
     */
    private function blueprint(): array
    {
        return [
            'venue' => [
                'unit' => 'per_event', 'tagline' => 'A timeless setting for your celebration',
                'blurb' => 'A versatile, light-filled space for ceremonies and receptions, with in-house coordination and flexible packages.',
                'services' => [['Full venue rental', 'from', 850000], ['Ceremony-only package', 'from', 250000]],
                'vendors' => [['The Glasshouse Estate', 'toronto', 1200000], ['Riverstone Hall', 'toronto', 950000], ['Evergreen Loft', 'toronto', 780000], ['Bayfield Manor', 'niagara', 1100000], ['The Foundry', 'hamilton', 690000], ['Lakeside Pavilion', 'kitchener-waterloo', 720000]],
            ],
            'catering' => [
                'unit' => 'per_person', 'tagline' => 'Seasonal menus your guests will remember',
                'blurb' => 'Locally-sourced, made-from-scratch wedding catering with plated, family-style and station options.',
                'services' => [['Plated dinner (per guest)', 'from', 12500], ['Cocktail stations (per guest)', 'from', 6500]],
                'vendors' => [['Thyme & Table Catering', 'toronto', 13500], ['Harvest Spoon Co.', 'toronto', 11000], ['Copperleaf Catering', 'toronto', 15500], ['Maple & Mortar', 'ottawa', 12000], ['Garden Fork Catering', 'london', 9800], ['Wildfig Kitchen', 'mississauga', 11800]],
            ],
            'photography' => [
                'unit' => 'per_event', 'tagline' => 'Timeless, candid wedding photography',
                'blurb' => 'We tell the story of your day in honest, light-filled images — from quiet getting-ready moments to the last dance.',
                'services' => [['Full-day coverage', 'from', 320000], ['Engagement session', 'fixed', 45000]],
                'vendors' => [['Aperture & Oak', 'toronto', 340000], ['Goldenhour Studio', 'toronto', 290000], ['Northlight Photography', 'toronto', 380000], ['Maple Lane Photo Co.', 'ottawa', 250000], ['Stonecroft Images', 'hamilton', 220000], ['Vineyard & Veil Photography', 'niagara', 310000]],
            ],
            'videography' => [
                'unit' => 'per_event', 'tagline' => 'Cinematic films of your wedding day',
                'blurb' => 'Documentary-style wedding films that capture the feeling of the day, not just the moments.',
                'services' => [['Highlight film', 'from', 280000], ['Full-day cinema package', 'from', 420000]],
                'vendors' => [['Reel & Ribbon Films', 'toronto', 320000], ['Lumen Wedding Films', 'toronto', 290000], ['Everafter Motion', 'toronto', 360000], ['Tributary Films', 'ottawa', 260000], ['Slowdance Cinema', 'hamilton', 240000], ['Westwind Films', 'london', 230000]],
            ],
            'florist' => [
                'unit' => 'per_event', 'tagline' => 'Garden-inspired florals for weddings',
                'blurb' => 'Seasonal, locally-grown wedding florals — from bridal bouquets to full installations.',
                'services' => [['Bridal party flowers', 'from', 95000], ['Ceremony & reception design', 'from', 250000]],
                'vendors' => [['Fern & Fawn Florals', 'toronto', 180000], ['The Petal Room', 'toronto', 150000], ['Wildbloom Studio', 'toronto', 220000], ['Stem & Stone', 'kitchener-waterloo', 130000], ['Juniper Floral Co.', 'ottawa', 160000], ['Vine & Vow', 'niagara', 175000]],
            ],
            'music' => [
                'unit' => 'per_event', 'tagline' => 'Music that keeps the floor full',
                'blurb' => 'Experienced wedding DJs and live musicians who read the room and keep your celebration moving.',
                'services' => [['Reception DJ + MC', 'from', 165000], ['Ceremony live music', 'fixed', 60000]],
                'vendors' => [['Velvet Sound DJs', 'toronto', 185000], ['The Nightingale Trio', 'toronto', 145000], ['Highnote Entertainment', 'toronto', 210000], ['Riverside Strings', 'ottawa', 120000], ['Boombox Collective', 'mississauga', 155000], ['The Lakefront Band', 'hamilton', 230000]],
            ],
            'bakery' => [
                'unit' => 'per_event', 'tagline' => 'Showstopping cakes & dessert tables',
                'blurb' => 'Hand-finished wedding cakes and dessert tables, baked to order with seasonal flavours.',
                'services' => [['Tiered wedding cake', 'from', 55000], ['Dessert table', 'from', 90000]],
                'vendors' => [['Sugarcraft Bakehouse', 'toronto', 65000], ['Tier & Twine Cakes', 'toronto', 58000], ['The Whisked Co.', 'toronto', 72000], ['Buttercup & Birch', 'london', 48000], ['Flour & Flora', 'ottawa', 60000], ['Sweet Vine Patisserie', 'niagara', 68000]],
            ],
            'officiant' => [
                'unit' => 'per_event', 'tagline' => 'Personal, heartfelt ceremonies',
                'blurb' => 'Warm, fully-personalised ceremonies — religious, secular or bilingual — written with you.',
                'services' => [['Personalised ceremony', 'fixed', 65000], ['Rehearsal + ceremony', 'fixed', 85000]],
                'vendors' => [['Tied & True Ceremonies', 'toronto', 65000], ['Vows & Co.', 'toronto', 60000], ['The Ceremony Studio', 'toronto', 75000], ['Capital Ceremonies', 'ottawa', 62000], ['Steeltown Vows', 'hamilton', 55000], ['Forest City Officiants', 'london', 58000]],
            ],
            'transportation' => [
                'unit' => 'per_hour', 'tagline' => 'Arrive in style, on time',
                'blurb' => 'Chauffeured wedding transportation — classic cars, limousines and guest shuttles.',
                'services' => [['Couple\'s car (per hour)', 'from', 18000], ['Guest shuttle (per hour)', 'from', 25000]],
                'vendors' => [['Royal Coach Limousines', 'toronto', 22000], ['Classic Ride Co.', 'toronto', 19000], ['Vintage Vow Cars', 'toronto', 26000], ['Capital Limos', 'ottawa', 18000], ['Steeltown Shuttles', 'hamilton', 16000], ['Estate Carriage Co.', 'niagara', 24000]],
            ],
            'attire' => [
                'unit' => 'per_event', 'tagline' => 'Bridal & formalwear, expertly fitted',
                'blurb' => 'A curated boutique of wedding gowns and suits with in-house alterations and styling.',
                'services' => [['Gown + alterations', 'from', 280000], ['Suit fitting', 'from', 95000]],
                'vendors' => [['The Ivory Atelier', 'toronto', 320000], ['Lace & Hem Bridal', 'toronto', 260000], ['Maison Blanc Bridal', 'toronto', 380000], ['Bourne Bridal', 'london', 220000], ['Capital Bridal House', 'ottawa', 250000], ['Cellar Door Suits', 'hamilton', 180000]],
            ],
            'beauty' => [
                'unit' => 'per_event', 'tagline' => 'Bridal hair & makeup that lasts all day',
                'blurb' => 'On-location wedding hair and makeup for you and your party, with trials and timeline planning.',
                'services' => [['Bridal hair & makeup', 'fixed', 35000], ['Bridal party (per person)', 'from', 12000]],
                'vendors' => [['Glow & Veil Beauty', 'toronto', 38000], ['Blush Artistry', 'toronto', 32000], ['The Glam Suite', 'toronto', 45000], ['Aurora Hair & Makeup', 'ottawa', 30000], ['Soft Focus Beauty', 'mississauga', 34000], ['Bloom Bridal Beauty', 'niagara', 36000]],
            ],
            'planner' => [
                'unit' => 'per_event', 'tagline' => 'Full-service & day-of wedding planning',
                'blurb' => 'From full planning to day-of coordination, we keep every detail on track so you can enjoy the day.',
                'services' => [['Day-of coordination', 'from', 180000], ['Full planning', 'from', 600000]],
                'vendors' => [['Ever & Co. Events', 'toronto', 650000], ['The Vow Atelier', 'toronto', 480000], ['Marigold Weddings', 'toronto', 320000], ['Capital Affairs', 'ottawa', 380000], ['Steeltown Soirées', 'hamilton', 260000], ['Vineyard Vows Planning', 'niagara', 420000]],
            ],
            'other' => [
                'unit' => 'per_event', 'tagline' => 'The finishing touches for your day',
                'blurb' => 'Photo booths, lighting, rentals and stationery to round out your celebration.',
                'services' => [['Photo booth (event)', 'fixed', 65000], ['Event lighting', 'from', 90000]],
                'vendors' => [['Cloud Nine Photo Booths', 'toronto', 65000], ['Sparkler & Co. Rentals', 'toronto', 120000], ['Lumen Lighting & AV', 'toronto', 140000], ['Tent & Table Rentals', 'kitchener-waterloo', 180000], ['Stationery by Sol', 'ottawa', 45000]],
            ],
        ];
    }
}
