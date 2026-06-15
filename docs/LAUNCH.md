# VowNook — Launch Dossier

_Security audit, pre-launch checklist, and go-to-market plan. Last updated 2026-06-14._

---

## 1. Security Audit

A full read of every state-changing controller, the middleware stack, auth/tenancy
model, file handling, session config, and dependency tree.

### Verdict

**Authorization and tenant isolation are excellent.** This is the part that usually
sinks a multi-tenant marketplace, and here it is uniformly correct. No IDOR, no
broken access control, no mass-assignment of financial fields found.

### What was verified as solid

- **Tenant isolation (couple side).** Every route-model-bound mutation
  (`guests`, `budget`, `vendors`, `seating`, `checklist`, `timeline`, `inspiration`,
  `crew`, `gallery`, `website`, `collaborators`) guards with
  `abort_unless($model->wedding_id === $current->id(), 404)`. You cannot read or
  mutate another wedding's row by guessing an ID.
- **Tenant isolation (vendor side).** Vendor-scoped controllers (`services`,
  `media`, `availability`, `inquiries`, `reviews`) guard on
  `$model->vendor_profile_id === $current->id()`.
- **Money flow.** `InquiryController::accept` derives every financial value
  (`total_cents`, `deposit_cents`, `platform_fee_cents`) from the server-side
  offer, never the request, computes the fee via `PlatformFee::for()`, and runs
  inside a `DB::transaction` with `lockForUpdate()` so two concurrent accepts
  can't double-book. Offer/booking statuses are server-controlled.
- **Reviews** can only be written by a couple with a matching booking, derive
  `vendor_profile_id`/`wedding_id` from the booking, and reject duplicates.
- **File serving** uses `Storage::response()` with tenancy checks; public website
  media sanitizes the filename with `basename()` and constrains `type` via a route
  `where()`. Uploads are validated as images, size-capped, and re-encoded through
  `ImageOptimizer` (which also strips EXIF/GPS metadata).
- **Admin** routes sit behind the `admin` middleware (`is_admin` flag, orthogonal
  to account type). `is_admin` and `plan` are **not** mass-assignable.
- **Session/cookies.** `httpOnly` on, `SameSite=lax`, JSON serialization (no PHP
  gadget-chain surface), cookie encryption enabled.
- **Rate limiting.** All public endpoints throttled (`120/min`); RSVP respond and
  contact form tightened further (`20/min`, `5/min`). Fortify provides login
  throttling.

### Findings fixed in this pass

| # | Severity | Finding | Resolution |
|---|----------|---------|------------|
| 1 | Medium | No defensive HTTP response headers (clickjacking, MIME-sniffing, referrer leak). | Added `SecurityHeaders` middleware: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, and HSTS over HTTPS. Covered by `SecurityHeadersTest`. |
| 2 | Low | `InquiryController::store` accepted a `vendor_service_id` without checking it belonged to the inquiried vendor (cross-vendor reference). | Now verified against `$vendor->services()`. |
| 3 | Infra | `@inertiajs/core` was imported directly but only present transitively — a clean `pnpm install` broke type-checking and could break builds. | Declared as an explicit direct dependency. |

### Open items (accepted risk / tracked, not blocking)

| # | Severity | Finding | Recommendation |
|---|----------|---------|----------------|
| 4 | Low | Public RSVP `respond` has no per-guest token — anyone who knows the public wedding slug can enumerate `guest_id`s (scoped to that wedding) and overwrite RSVP/dietary notes. Throttled 20/min. | Acceptable for v1 (standard for public RSVP). If abused, add a per-guest RSVP token in the shareable link. |
| 5 | Low (dev only) | `shell-quote` < 1.8.4 critical advisory, transitive via `concurrently` (a dev script runner). **Not in the production bundle.** | Clear at leisure with a pnpm override or by bumping `concurrently`; no production exposure. |
| 6 | Hardening | No Content-Security-Policy. A meaningful CSP needs per-asset nonces wired through Vite/Inertia. | Post-launch hardening. The other headers are in place now. |
| 7 | Process | Admin accounts are not required to use 2FA (Fortify 2FA exists and works). | Enable 2FA on every admin/owner account before launch. |

### When Stripe lands (Phase 4) — payment security must-haves

- Verify webhook signatures (`Stripe-Signature`); reject unsigned.
- Idempotency keys on PaymentIntent creation.
- Never trust client-sent amounts — already the established pattern here.
- Keep PCI scope on Stripe (Elements/Checkout); never touch raw card data.
- Use destination charges with `application_fee_amount`; assert the fee lands on
  the platform and the remainder on the connected account (test-mode webhook tests).

---

## 2. Pre-Launch Checklist

### Environment & secrets (do first)
- [ ] `APP_DEBUG=false` (never expose stack traces in prod).
- [ ] `APP_KEY` generated and stored as a secret (not in the repo).
- [ ] `APP_ENV=production`, `APP_URL=https://<domain>`.
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`.
- [ ] `TrustProxies` configured for the host/load balancer so HTTPS + secure
      cookies are detected correctly (and HSTS fires).

### Data & infrastructure
- [ ] PostgreSQL provisioned (`DB_CONNECTION=pgsql`); run `php artisan migrate --force`.
- [ ] S3/R2 for uploads (`FILESYSTEM_DISK=s3`, `AWS_*`), region `ca-central-1` for
      Canadian data residency.
- [ ] **Queue worker running** (`php artisan queue:work` under Supervisor/systemd).
      Notifications are queued — without a worker, **no emails go out**.
- [ ] Real mail: `MAIL_MAILER=smtp` or Resend. Verify a test inquiry actually emails.
- [ ] **Scheduler cron** (now required): `* * * * * php artisan schedule:run`.
      Drives lifecycle email (welcome, milestones, weekly digest, RSVP reminders,
      vendor re-engagement), plan-comp expiry, and the post-wedding flow. Without
      it, none of those send. Needs the queue worker (above) too.
- [ ] **CASL**: set `MAIL_CASL_ADDRESS` (a real physical mailing address) — it’s
      rendered in every marketing email footer alongside the unsubscribe link.
- [ ] Automated database backups (daily + retention).
- [ ] Error monitoring (Sentry free tier) wired in.
- [ ] `php artisan config:cache route:cache view:cache` in the deploy build.

### Security (mostly done — verify)
- [x] Security headers middleware shipping.
- [x] Tenant isolation verified across all controllers.
- [x] Rate limiting on public endpoints.
- [ ] Force HTTPS (redirect HTTP→HTTPS at the edge).
- [ ] 2FA enabled on all admin accounts.
- [ ] Run `composer audit` on the production host (PHP deps) before each deploy.

### Legal & trust (built — review before go-live)
- [x] Terms, Privacy (PIPEDA), Contact pages live and linked.
- [ ] **Have a lawyer review Terms + Privacy** before real money flows.
- [ ] Confirm the success-fee numbers match in three places: Terms, landing
      pricing, and `config/marketplace.php`.
- [ ] PIPEDA: confirm the account-deletion path hard-deletes/anonymizes PII
      (guest dietary/health data especially) within the stated 30 days.

### Product readiness
- [x] 242 tests green, types clean, production build passes.
- [ ] Seed or onboard the first cohort of real vendors (see GTM below) so the
      marketplace isn't empty on day one.
- [ ] Smoke-test the full loop on production: couple signup → browse → inquiry →
      vendor offer → accept → booking appears in workspace.
- [ ] Smoke-test planner signup → HQ → new client → template apply.

### Deferred (post-launch, documented)
- Stripe Connect payments (Phase 4) — the only thing that moves real money.
- Content-Security-Policy with nonces.
- French/Quebec bilingual (Bill 96) — year-two market.
- Image thumbnail variants (currently single optimized size).

---

## 3. Marketing & Go-To-Market (Canada-first)

### The core problem to beat: cold-start
A two-sided marketplace is worthless to couples with no vendors, and worthless to
vendors with no couples. **Solve the vendor side first** — supply creates the SEO
surface and the reason for couples to show up.

### Phase 0 — Supply before demand (weeks 0–6)
- **Hand-recruit 30–50 vendors in one metro** (Greater Toronto Area first — dense,
  high wedding spend, English-first so no Bill 96 blocker yet). Concentrate, don't
  spread; a couple searching "Toronto wedding florist" must find a real choice.
- **The pitch that works:** free listing, no contract, no monthly fee — you only
  pay when you win a booking (8% up to $5k, 5% above, capped $1k). This beats
  WeddingWire/The Knot's pay-upfront-for-leads model where vendors pay whether or
  not they book anyone.
- Offer "founding vendor" status (badge + featured placement) to the first cohort
  in exchange for completing a full profile with real portfolio photos.

### Phase 1 — Programmatic SEO (the durable growth engine)
- The marketplace already emits `LocalBusiness` JSON-LD and a sitemap. Build out
  **category × city landing pages** ("Wedding Photographers in Toronto",
  "Caterers in Mississauga") generated from real vendor data. This is how couples
  discover you on Google without paid spend, and it compounds.
- Each published vendor profile is an indexable page working for you 24/7.
- Guard against thin/duplicate pages: only generate a city×category page once it
  has ≥3 real vendors.

### Phase 2 — Convert browsers to bookings
The research-backed #1 lever, **already built**: the "responds in ~Xh" badge.
Fast vendor response is the single biggest driver of inquiry→booking. Lean on it:
- Surface it on cards and profiles (done).
- Nudge vendors by email when an inquiry is unanswered for N hours.
- Consider a "Fast responder" filter/sort once enough data exists.

### Phase 3 — Demand-side acquisition
- **Trust as the wedge vs incumbents:** every review is tied to a real booking —
  no pay-to-play. Say this loudly; it's WeddingWire's known weak spot.
- **Planners as a force multiplier:** the planner HQ tier ($499/yr, unlimited
  client weddings) turns one signup into 10–30 weddings, each of which sources
  vendors through your marketplace. Recruit planners directly — they bring both
  supply relationships and demand volume.
- **Content/SEO for couples:** budget calculators, checklist templates, "questions
  to ask your photographer" — capture top-of-funnel planning searches and route
  them into the free planning studio.
- **The free planning studio is the demand magnet:** couples come for free guest
  list / budget / website, then discover the marketplace from inside the product.

### Positioning one-liner
> The free wedding-planning studio with a vendor marketplace where every review is
> real and you only pay your vendor when you actually book.

### Metrics to watch from day one
- Vendor activation rate (signup → published profile).
- Inquiry response time (median) and inquiry→offer→booking conversion.
- City×category pages with ≥3 vendors (SEO surface area).
- Planner signups and weddings-per-planner.

### Deliberately deferred
- Paid acquisition (Google/Meta ads) — don't buy demand before supply and SEO are
  in place; you'll pay to send couples to an empty marketplace.
- Quebec / French market — needs Bill 96 compliance; year two.
- Registry/cash-fund affiliate revenue — a proven secondary stream (Joy's model),
  worth building once the core loop is humming.

---

## 4. SEO — what's built (Ontario-first organic engine)

The SEO-critical metadata lives in the document `<head>`, which Laravel
server-renders via the blade root view (`Inertia::render(...)->withViewData(['seo' => ...])`)
**regardless of whether the body is server- or client-rendered** — so crawlers and
AI assistants get full metadata and structured data today, before SSR lands.

**Shipped & tested (242 + 8 SEO tests green):**
- **Server-rendered per-page SEO** (`App\Support\Seo`): title, description, canonical,
  Open Graph, Twitter, and JSON-LD — on the marketplace, vendor profiles, and the
  programmatic pages.
- **Programmatic Ontario pages** (`PublicLocalController`):
  - `/{category}` hubs — e.g. `/wedding-photographers` (12 categories).
  - `/{category}/{city}` — e.g. `/wedding-photographers/toronto` (× 7 metros:
    Toronto, Ottawa, Mississauga, Hamilton, London, Kitchener-Waterloo, Niagara).
  - **Quality gate:** a city page is `noindex` until it has ≥3 vendors (prevents
    Google doorway/thin-content penalties). Expands automatically as inventory grows.
  - Unique templated intro content, internal linking (hub ⇄ city ⇄ sibling
    categories), breadcrumbs.
- **Structured data:** Organization + WebSite (SearchAction) site-wide;
  LocalBusiness + AggregateRating on vendor profiles; CollectionPage/ItemList on
  programmatic pages; BreadcrumbList throughout.
- **robots.txt** (dynamic): blocks private app areas, welcomes AI crawlers
  (GPTBot/ClaudeBot/PerplexityBot/Google-Extended), points to the sitemap.
- **sitemap.xml:** home, marketplace, all category hubs, quality-gated city pages,
  vendor profiles, published wedding sites, with `lastmod`.
- **llms.txt** for AI-assistant discovery (GEO).
- Filtered marketplace URLs (`?category=`) carry `noindex` + canonical to avoid
  duplicating the programmatic pages.

**SSR — wired and production-ready; activate on staging:**
- The blade root view uses the `@inertia` directive, `config/inertia.php` points at
  the `bootstrap/ssr/app.js` bundle (`pnpm build:ssr`), and the node SSR server
  renders the real pages correctly (verified directly). The gateway dispatches
  successfully from a controller (returns ~35 KB of server-rendered HTML).
- It is **not verifiable on local `php artisan serve`** — that dev server is
  single-threaded and fails to reach the SSR process mid-request
  (`cURL error 7: Failed to connect to 127.0.0.1:13714`). This is the documented
  reason Inertia SSR isn't run on `php artisan serve`.
- **To activate (staging/production):** set `INERTIA_SSR_ENABLED=true`, run
  `php artisan inertia:start-ssr` under Supervisor alongside php-fpm, then curl a
  page and confirm `<div id="app">` contains rendered markup. Runbook is in
  `.env.example`. Locally SSR stays off (env default) so dev isn't slowed by the
  failed connect.
- Note: the SEO `<head>` is server-rendered regardless of SSR, so SSR only adds
  full-body crawlability — which Google JS-renders in the meantime.

**Head cleanup (done):** every public page now has unique, non-duplicated,
server-rendered SEO. Static/legal pages (home, how-it-works, terms, privacy,
contact) render via `PublicPageController` with proper titles/descriptions/canonical/OG;
the React `<Head>` no longer sets duplicate `<meta>` (verified: exactly one
description tag per page). Guest-utility pages (RSVP, seat finder) and unpublished
wedding sites are `noindex`.

**Remaining SEO work:**
- **Blog system** (posts table, admin authoring, `/blog`, BlogPosting schema,
  Ontario-targeted content) — the top-of-funnel + GEO fuel; not yet built.

**Reality check:** this builds the *engine* that makes ranking possible. "#1 in
Ontario" is earned over 3–9 months via this + real vendor inventory + published
content + backlinks. No code ranks you overnight; this means you win on every
technical/structural signal when Google evaluates you.
