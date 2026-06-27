# VowNook — Deploy Day Checklist (Beta Launch)

Work top to bottom. This ships the 6 pending commits, seeds the launch data, and
opens the FOUNDING50 beta — with Stripe in **test mode** and the AI autopilots
**off** until the app is proven.

---

## 1. Ship the code
- [ ] `git push origin main`
- [ ] Laravel Cloud → **Deploy** (migrations auto-run; this batch adds one table: `local_contents`)
- [ ] Wait for the deploy to go green

## 2. Seed the launch data (run in the Laravel Cloud console — deploys do NOT run seeders)
> Note: `db:seed` needs `--force` in production (it skips the "APPLICATION IN PRODUCTION"
> confirmation prompt, which the non-interactive Cloud runner can't answer). These
> seeders only ADD data — they never wipe anything.
- [ ] `php artisan db:seed --class=BlogPostSeeder --force` — publishes the "Plan a wedding for free" article
- [ ] `php artisan db:seed --class=FoundingPromoSeeder --force` — activates the **FOUNDING50** code
- [ ] `php artisan marketplace:demo` — 77 demo vendors + images (custom command, no prompt)

## 3. Confirm environment (Laravel Cloud → Environment)
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_ENCRYPT=true`
- [ ] Stripe = **TEST** keys (`pk_test…` / `sk_test…`) — beta testers pay with card `4242 4242 4242 4242`
- [ ] Leave OFF for now: `BLOG_AUTOPILOT_ENABLED`, `LOCAL_SEO_AUTOFILL_ENABLED` (turn on after the app is proven)

## 4. Smoke-test the must-work paths (10 min, before inviting anyone)
- [ ] Sign up a brand-new account → lands on dashboard (**no white page** — the fix)
- [ ] Hard refresh a page (Ctrl/Cmd+Shift+R) → loads fine (stale-asset recovery)
- [ ] Invite a collaborator by email → open the link in **incognito** → accept → gets in (your friend's flow)
- [ ] Marketplace → open a demo vendor → **images show** → "Request a quote" works
- [ ] Settings → Plan → "Have a code?" → `FOUNDING50` → **Atelier unlocks**
- [ ] Blog post live at `/blog/how-to-plan-a-wedding-for-free-ontario`
- [ ] A city page shows vendors, e.g. `/wedding-photographers/toronto`

## 5. Launch the beta
- [ ] Post the **FOUNDING50 founder post** (in `docs/LAUNCH-MARKETING.md`) to LinkedIn / personal
- [ ] Invite ~5–15 testers (friends, family, a few Ontario couples)
- [ ] Start **founding-vendor outreach** in parallel (templates in `docs/LAUNCH-MARKETING.md`)
- [ ] Tell testers: *"Beta — payments are in test mode, tell me anything that breaks."*

## 6. Watch
- [ ] Laravel Cloud → **Logs** (watch for errors)
- [ ] Admin console → new signups / activity feed
- [ ] Collect feedback in one place

---

## Going wider later (after the beta is clean)
- [ ] Swap Stripe **test → live** keys (real payments)
- [ ] **Enable SSR**: `INERTIA_SSR_ENABLED=true` + deploy runs `pnpm build:ssr` + run `php artisan inertia:start-ssr` as a managed process — biggest SEO/AI-crawler win
- [ ] **Purge demo vendors** once real ones join: `php artisan marketplace:demo --purge`
- [ ] Turn on the autopilots: `BLOG_AUTOPILOT_ENABLED=true`, `LOCAL_SEO_AUTOFILL_ENABLED=true` (test once each with `--force` first)
- [ ] Add analytics keys (GA4 / Clarity / Search Console) + submit `sitemap.xml`
- [ ] Mirror travel-affiliate keys (`STAY22_ID`, `TRAVELPAYOUTS_*`)
- [ ] Remove duplicate `_dmarc` DNS record

---

## Rollback / undo
- Demo vendors: `php artisan marketplace:demo --purge`
- A bad autopilot post: unpublish it in `/admin/blog`
- FOUNDING50: deactivate it in the Filament panel (`/manage` → Promo Codes) or set `is_active=false`
