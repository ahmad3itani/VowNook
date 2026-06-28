# VowNook — 100-Post Blog SEO Plan

The strategy behind the expanded `BlogTopics` queue (`app/Support/Blog/BlogTopics.php`),
which the `blog:autopilot` command drips out automatically.

---

## 1. The model: topic clusters (hub & spoke)

Instead of 100 random posts, we build **~14 topic clusters**. Each cluster has:
- a **pillar** (the broad hub page — e.g. *"How Much Does a Wedding Cost in Ontario"*)
- **6–12 spokes** (specific long-tail keywords — e.g. *"Average Wedding Cost in Ottawa"*)

Spokes link **up** to their pillar and **across** to siblings; this is what tells
Google the site has topical authority on Ontario weddings — and it's far stronger
than 100 disconnected posts. Five pillars already exist (the seeded posts); the
rest publish first so spokes always have something to link to.

## 2. Keyword strategy

We target **search intent**, not vanity volume (no live keyword tool here — validate
real volumes later in Search Console once impressions land). Three intent buckets:

- **Informational / top-of-funnel** ("how much does a wedding cost", "wedding planning timeline") → traffic + trust, feeds the planning tools.
- **Local commercial** ("wedding photographers in Toronto", "winery weddings Niagara") → high intent, feeds the marketplace. Overlaps the programmatic city pages — the blog supports them.
- **Decision / comparison** ("band vs DJ", "all-inclusive vs DIY venue") → couples close to booking.

Every title is **keyword-first** and matches how couples actually search.

## 3. GEO (AI-citation) optimization

Built into the writer's system prompt + structure so ChatGPT / Perplexity / AI
Overviews cite VowNook:
- **Answer-first** opening paragraph (the citable passage).
- Clear `##` sections + lists (extractable).
- Honest, specific, Ontario-grounded; **no fabricated stats**.
- Schema (`BlogPosting`) already server-rendered; `llms.txt` already lists the blog.
- *(Enable SSR so non-JS AI crawlers can read the body — the multiplier here.)*

## 4. Internal linking ("link all")

The autopilot now links each new post to **its cluster pillar + already-published
siblings** (passed into the writer at generation time, so links never 404). As a
cluster fills in, the web of internal links grows automatically. Plus the standing
links to `/dashboard` (free tools) and `/marketplace` (vendors) for conversion.

## 5. Publishing cadence (the drip — NOT all at once)

- `blog:autopilot` publishes on a schedule, **de-duped**, quality-gated.
- Default cadence: **weekdays** (~5/week) → ~100 posts over ~4–5 months. Steady,
  natural, never a dump. Tune with `BLOG_AUTOPILOT_PER_RUN` or the schedule frequency.
- **Pillars publish first** (they're ordered first in the queue), so the hubs exist
  before their spokes link to them.
- Turn it on with `BLOG_AUTOPILOT_ENABLED=true` when you're ready.

## 6. The clusters

| # | Cluster | Pillar | Spokes |
|---|---|---|---|
| 1 | Wedding costs | *(seeded)* How Much Does a Wedding Cost in Ontario | ~12 (by city, by category, who pays, micro, hidden) |
| 2 | Planning timeline | *(seeded)* Ontario Wedding Planning Timeline | ~9 (6-month, day-of, mistakes, licence, elope) |
| 3 | Free planning & tools | *(seeded)* How to Plan a Wedding for Free | ~6 (free website, seating, guest list, budget, RSVP) |
| 4 | Venues | *(seeded)* 12 Questions to Ask a Venue | ~12 (types, outdoor, winery, barn, capacity, by city) |
| 5 | Photography & video | *(seeded)* How to Choose a Photographer | ~8 (cost, styles, engagement, videographer) |
| 6 | Choosing vendors | How to Choose Your Wedding Vendors | ~9 (florist, DJ, caterer, cake, beauty, officiant, reviews) |
| 7 | Flowers & decor | A Guide to Wedding Flowers in Ontario | ~6 (seasonal, bouquet cost, centerpieces, palettes) |
| 8 | Food & drink | A Guide to Wedding Catering in Ontario | ~6 (styles, bar, dietary, late-night, tasting) |
| 9 | Music & entertainment | A Guide to Wedding Music & Entertainment | ~5 (DJ cost, first dance, ceremony music, band) |
| 10 | Attire & beauty | Wedding Attire & Beauty Guide | ~6 (dress timeline/budget, suits, trials, skincare) |
| 11 | Guests & RSVP | The Wedding Guest List & RSVP Guide | ~7 (build/cut list, plus-ones, seating, invites) |
| 12 | Honeymoon & travel | The Honeymoon Planning Guide | ~5 (budget, destinations, fund, when to book) |
| 13 | Registry & gifts | The Wedding Registry Guide | ~5 (cash vs items, honeymoon fund, etiquette, thank-yous) |
| 14 | Seasons & getting married in Ontario | Getting Married in Ontario | ~6 (best season, winter/fall/summer, long weekends) |

≈ **100 posts** total (5 existing pillars + ~95 new). All defined as stable,
de-dupe-keyed entries in `BlogTopics`.

## 7. How to run it
1. Deploy (the expanded queue + linking ship in code).
2. `php artisan blog:autopilot --force` once to sanity-check a post.
3. Set `BLOG_AUTOPILOT_ENABLED=true` + ensure the scheduler runs → it drips weekdays.
4. Watch Search Console: as clusters fill, impressions on local + planning queries should climb.
