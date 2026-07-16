<?php

namespace App\Support\Seo;

use App\Enums\VendorCategory;
use App\Support\Budget\BudgetAllocator;
use App\Support\OntarioCities;

/**
 * Deterministic, data-backed local-guide copy — a no-API-key alternative to the
 * AI {@see LocalContentWriter}. It weaves real per-city cost ranges
 * ({@see LocalCosts}), city context and genuine category-specific advice into a
 * unique guide + FAQs for each programmatic page, so the tranche of indexed
 * pages is genuinely useful rather than thin. Reproducible and reviewable (it's
 * seeded, not generated at runtime), and it never invents statistics, awards or
 * business names — every cost is framed as an estimate (Competition Act / CPA).
 */
class LocalGuide
{
    /**
     * Per-category advice bank. `lead` is the typical booking lead time; the
     * `guidance` paragraph and `faq` are genuine, honest guidance for couples.
     *
     * @var array<string, array{singular:string, lead:string, guidance:string, faq:array{question:string, answer:string}}>
     */
    private const CATS = [
        'venue' => [
            'singular' => 'wedding venue',
            'lead' => '12–18 months',
            'guidance' => "When you tour a venue, get the true all-in number in writing: rental or site fee, food and beverage minimums, service charges and gratuity, and any charges for extra hours, security or cleanup. Ask what's included (tables, chairs, linens, on-site coordinator) versus what you'll rent separately, and confirm the real capacity for a seated dinner with a dance floor — not the theatre-style maximum.",
            'faq' => ['question' => 'What should a wedding venue quote actually include?', 'answer' => 'A clear quote covers the rental or site fee, any food-and-beverage minimum, service charge and tax, the hours you have the space, and what furniture and staff are included. Ask specifically about overtime, corkage and cleanup fees — those are where surprise costs hide.'],
        ],
        'catering' => [
            'singular' => 'wedding caterer',
            'lead' => '9–14 months',
            'guidance' => "Caterers usually price per plate, but the headline number rarely tells the whole story — ask whether staffing, rentals (plates, glassware, linens), bar service and gratuity are included or added on top. Book a tasting before you sign, confirm how they handle dietary restrictions and children's meals, and check whether your venue requires an approved caterer or allows outside vendors.",
            'faq' => ['question' => 'Is wedding catering priced per person?', 'answer' => 'Usually yes — most Ontario caterers quote a per-plate price, then add staffing, rentals, bar and gratuity. Always ask for an all-in estimate for your guest count so you can compare quotes fairly, and book a tasting first.'],
        ],
        'photography' => [
            'singular' => 'wedding photographer',
            'lead' => '9–14 months',
            'guidance' => "Judge photographers on full galleries, not just highlight reels — a strong one shoots consistently from getting-ready to the last dance, and in the light your day will actually have. Decide early whether you want a second shooter (worth it for larger weddings or two getting-ready locations), and confirm how many hours of coverage, how many edited images, and what album or print rights each package includes.",
            'faq' => ['question' => 'Do I need a second wedding photographer?', 'answer' => 'For weddings over roughly 100 guests, two getting-ready locations, or a large dance floor, a second shooter is usually worth it — you get broader, safer coverage. Smaller single-venue weddings are often well served by one experienced photographer.'],
        ],
        'videography' => [
            'singular' => 'wedding videographer',
            'lead' => '8–12 months',
            'guidance' => "Watch a few full films, not just 60-second teasers, and listen to how they handle audio — vows and speeches live or die on clean sound from lav mics and a good board feed. Confirm what you're getting: a short highlight film, a longer documentary edit, raw footage, drone coverage and how many shooters. If you want both photo and video, ask whether the two teams have worked together.",
            'faq' => ['question' => 'What is the difference between a highlight film and a full film?', 'answer' => 'A highlight film is a polished 3–6 minute story set to music; a full or documentary edit runs 20–60+ minutes and includes the full ceremony and speeches. Many couples book a package with both, plus optional drone and raw footage.'],
        ],
        'florist' => [
            'singular' => 'wedding florist',
            'lead' => '8–12 months',
            'guidance' => "Bring photos and your colour palette, but be honest about budget up front — a good florist will tell you which blooms are in season (and affordable) around your date and where to spend for impact. Ask what's included in centrepiece pricing (vessels, rentals, setup and teardown), and consider repurposing ceremony arrangements at the reception to stretch the budget.",
            'faq' => ['question' => 'How can I save on wedding flowers?', 'answer' => 'Choose blooms in season for your date, focus spend on high-impact moments (the arch, head table, bouquets), repurpose ceremony florals at the reception, and lean on greenery. A good florist will design to your budget if you share it early.'],
        ],
        'music' => [
            'singular' => 'wedding DJ or band',
            'lead' => '9–12 months',
            'guidance' => "Decide the vibe first — a DJ covers the widest range of eras and keeps the night seamless, while a live band brings energy that's hard to beat (and a bigger budget and footprint). Either way, confirm they'll MC and read the room, ask about a wireless mic for speeches, and check whether ceremony or cocktail-hour sound is included or a separate add-on.",
            'faq' => ['question' => 'Should we book a wedding DJ or a live band?', 'answer' => 'A DJ is more affordable and covers every genre with seamless transitions; a live band brings unmatched energy but costs more and needs more space. Many couples do both — a band for part of the night, a DJ for the rest.'],
        ],
        'bakery' => [
            'singular' => 'wedding cake baker',
            'lead' => '4–8 months',
            'guidance' => "Book a tasting and bring inspiration photos — pricing is usually per slice and climbs with tiers, sugar flowers and hand-piping. Ask about delivery and setup (a tiered cake is not a DIY transport job), whether they can match dietary needs, and consider a smaller display cake plus a sheet cake or dessert table to serve more guests for less.",
            'faq' => ['question' => 'How is a wedding cake priced?', 'answer' => 'Most bakers price per slice, so cost scales with guest count and complexity — fondant, sugar flowers and multiple tiers add up. A display cake paired with a hidden sheet cake or dessert table is a common way to serve everyone for less.'],
        ],
        'officiant' => [
            'singular' => 'wedding officiant',
            'lead' => '6–10 months',
            'guidance' => "In Ontario your ceremony must be solemnized by an authorized officiant (a registered religious official or a licensed civil officiant), and you'll need a marriage licence obtained within 90 days of the date. Meet them first — a great officiant tailors the script, runs the rehearsal, and handles the legal paperwork and witnesses so nothing is missed on the day.",
            'faq' => ['question' => 'What do I legally need to get married in Ontario?', 'answer' => 'You need a marriage licence (valid for 90 days, from any Ontario municipal office) and a ceremony performed by an authorized officiant in front of two witnesses. Your officiant registers the completed licence afterward.'],
        ],
        'transportation' => [
            'singular' => 'wedding transportation provider',
            'lead' => '4–8 months',
            'guidance' => "Map your day first — getting-ready locations, ceremony, photos and reception — then match the vehicle and hours to it. Most companies bill by the hour with a minimum, so confirm travel time, gratuity and whether guest shuttles are an option. For larger weddings, a shuttle loop for guests is often better value (and safer) than several limos.",
            'faq' => ['question' => 'How much wedding transportation do we actually need?', 'answer' => 'Base it on your timeline and party size. A couple may only need one vehicle for photos and arrivals; if guests are travelling between venues or from hotels, a shuttle loop is usually safer and better value than multiple limos.'],
        ],
        'attire' => [
            'singular' => 'bridal or suit boutique',
            'lead' => '9–12 months',
            'guidance' => "Start early — made-to-order gowns can take 4–6 months to arrive, plus 6–8 weeks for alterations, which are almost always extra. Book appointments, keep your venue and season in mind, and budget separately for alterations, undergarments and accessories. For suits, decide early between buying and renting based on how often it'll be worn again.",
            'faq' => ['question' => 'How early should I buy my wedding dress?', 'answer' => 'Order 9–12 months out: made-to-order gowns often take 4–6 months to arrive, then 6–8 weeks for alterations. Off-the-rack and rental options are faster if your date is sooner.'],
        ],
        'beauty' => [
            'singular' => 'wedding hair and makeup artist',
            'lead' => '6–10 months',
            'guidance' => "Book a trial before the wedding, ideally in similar lighting and with your accessories, and photograph the results on your phone. Confirm whether the quote covers just the couple or the full party, whether they travel to you, and how many hours and touch-ups are included. Ask about longevity products (airbrush, lash options) if you want the look to last through a long day.",
            'faq' => ['question' => 'Should I do a hair and makeup trial?', 'answer' => 'Yes — a trial confirms the look, the products and the timing before the day. Bring your accessories and photos, and shoot the result on your phone in natural light so there are no surprises.'],
        ],
        'planner' => [
            'singular' => 'wedding planner',
            'lead' => '10–16 months',
            'guidance' => "Match the service to what you actually need: full planning (from concept to day-of, best if you're short on time or planning from away), partial planning (you've started, they steer and refine), or month-of coordination (you plan, they run the day). Ask what's included, how many meetings and hours, and whether they'll manage the timeline and vendor logistics on the day itself.",
            'faq' => ['question' => 'What is the difference between full planning and month-of coordination?', 'answer' => 'Full planning covers everything from concept and budget to vendors and the day itself; month-of (or day-of) coordination means you plan the wedding and the coordinator takes over the timeline, vendors and logistics a few weeks out. Partial planning sits in between.'],
        ],
    ];

    public function __construct(private LocalCosts $costs = new LocalCosts) {}

    /**
     * Ontario category-hub guide.
     *
     * @return array{intro:string, faqs:list<array{question:string, answer:string}>}|null
     */
    public function hub(VendorCategory $cat): ?array
    {
        return $this->build($cat, null);
    }

    /**
     * City × category guide.
     *
     * @return array{intro:string, faqs:list<array{question:string, answer:string}>}|null
     */
    public function city(VendorCategory $cat, string $citySlug): ?array
    {
        return OntarioCities::exists($citySlug) ? $this->build($cat, $citySlug) : null;
    }

    /**
     * @return array{intro:string, faqs:list<array{question:string, answer:string}>}|null
     */
    private function build(VendorCategory $cat, ?string $citySlug): ?array
    {
        $meta = self::CATS[$cat->value] ?? null;
        $cost = $this->costs->for($cat, $citySlug);

        if ($meta === null || $cost === null) {
            return null; // Other, or an unpriced category — no guide.
        }

        $noun = $cat->seoNoun();
        $nounLower = mb_strtolower($noun);
        $singular = $meta['singular'];
        $lead = $meta['lead'];
        $display = $cost['display'];
        $note = $cost['note'];
        $cityName = $citySlug !== null ? OntarioCities::name($citySlug) : null;

        $intro = $cityName !== null
            ? $this->cityIntro($cat, $citySlug, $cityName, $nounLower, $singular, $display, $note, $meta['guidance'], $lead)
            : $this->hubIntro($nounLower, $singular, $display, $note, $meta['guidance'], $lead);

        $place = $cityName !== null ? "{$cityName}, Ontario" : 'Ontario';
        $placeShort = $cityName ?? 'Ontario';

        $faqs = [
            [
                'question' => "How much do {$nounLower} cost in {$placeShort}?",
                'answer' => $cityName !== null
                    ? "Typical {$nounLower} in {$cityName} run about {$display} ({$note}). It's an estimate — your final quote depends on your date, guest count, hours and style."
                    : "Across Ontario, {$nounLower} typically run about {$display} ({$note}), and cost more in Toronto, the GTA and destination regions like Muskoka and Niagara. Treat it as an estimate that moves with your date, guest count and style.",
            ],
            [
                'question' => "How far in advance should I book a {$singular} in {$placeShort}?",
                'answer' => "Aim to book {$lead} ahead. In {$place}, the most in-demand {$nounLower} and peak summer and early-fall Saturdays are reserved first, so lock yours in sooner if your date is fixed.",
            ],
            $meta['faq'],
            [
                'question' => "How does VowNook help me find {$nounLower} in {$placeShort}?",
                'answer' => "VowNook is a free wedding-planning studio and an Ontario vendor marketplace. Browse {$place} {$nounLower}, compare reviews that are tied to real bookings (no pay-to-play placement), request quotes for free, and keep your guest list, budget and checklist in one place.",
            ],
        ];

        return ['intro' => $intro, 'faqs' => $faqs];
    }

    private function cityIntro(VendorCategory $cat, string $citySlug, string $cityName, string $nounLower, string $singular, string $display, string $note, string $guidance, string $lead): string
    {
        $blurb = OntarioCities::get($citySlug)['blurb'] ?? '';
        $tier = $this->tier($citySlug);

        $p1 = "Looking for a {$singular} in {$cityName}? {$blurb} That character shapes both who's available and what you'll pay: typical {$nounLower} in {$cityName} run **{$display}** ({$note}) — an estimate that moves with your date, guest count and style, and reflects {$cityName} being {$tier}.";
        $p3 = "The best {$nounLower} in {$cityName} book up early, so aim to reserve yours **{$lead} ahead** — sooner for peak summer and early-fall dates. On VowNook every listing is reviewed before it goes live and every review is tied to a real booking, so you can compare {$cityName} {$nounLower} honestly, request quotes for free, and plan the rest of your day in the same place.";

        return "{$p1}\n\n{$guidance}\n\n{$p3}";
    }

    private function hubIntro(string $nounLower, string $singular, string $display, string $note, string $guidance, string $lead): string
    {
        $p1 = "Comparing {$nounLower} across Ontario? What you'll pay varies widely by region — expect the highest prices in Toronto and the GTA and in destination areas like Muskoka and Niagara wine country, and more room in southwestern and northern Ontario. As a province-wide guide, {$nounLower} typically run **{$display}** ({$note}), with your final cost shaped by date, guest count and style.";
        $p3 = "Wherever you're marrying in Ontario, aim to book your {$singular} **{$lead} ahead**. Browse {$nounLower} by city below, or start a free VowNook planning studio — every listing is reviewed, every review is tied to a real booking (no pay-to-play), and requesting quotes is always free.";

        return "{$p1}\n\n{$guidance}\n\n{$p3}";
    }

    /** A short "market tier" phrase derived from the city's cost multiplier. */
    private function tier(string $citySlug): string
    {
        $index = (new BudgetAllocator)->cityIndex($citySlug);

        return match (true) {
            $index >= 1.12 => "one of Ontario's premium wedding markets",
            $index >= 1.03 => 'a sought-after, higher-cost Ontario market',
            $index >= 0.97 => 'a mid-range Ontario market',
            default => 'one of Ontario\'s more affordable wedding markets',
        };
    }
}
