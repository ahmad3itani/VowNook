# VowNook — Deploy Runbook (Laravel Cloud)

Everything code-side is ready. Laravel Cloud handles Postgres, queue workers, the
scheduler, and SSL for you, so this is mostly clicks + pasting secrets.
**[you]** = needs your accounts/payment/secrets (I can't do these). **[auto]** = handled.

---

## Plan choice
- **Sandbox (free)** — use first to deploy + smoke-test. Hibernates when idle; no custom domain.
- **Production (usage-based)** — for launch. Pick the **smallest compute** + **smallest
  serverless Postgres**; scale later with a slider. Verify current rates at cloud.laravel.com/pricing.

---

## 1. Push the repo [you]
The remote is live at `github.com/ahmad3itani/VowNook`. To ship work:
```bash
git status            # ⚠️ ALWAYS look first (see the warning below)
git add <paths>       # stage explicitly
git commit -m "..."
git push origin main  # Laravel Cloud deploys on push
```
> ⚠️ **Never `git add -A` in this repo without reading `git status` first.** `facebook-kit/`
> holds live Facebook/Instagram Graph API tokens (`token.txt`, `ig-token.txt`). The token
> files are now gitignored (`/facebook-kit/*token*.txt`), but stage by path, not blindly.

## 2. Create the project [you]
1. Sign up at **cloud.laravel.com** → create an **Organization**.
2. **New Project** → connect GitHub → pick the `vownook` repo, branch `main`.
3. Region: choose the closest available (US is typical on Laravel Cloud — note for Canadian
   data residency; if that's a dealbreaker, the Forge+Toronto path in git history is the fallback).

## 3. Add a database [auto-ish]
- In the project, **Add → Database → Postgres** (serverless, smallest). Laravel Cloud
  **auto-injects** `DB_*` env vars — you don't paste them.

## 4. Object storage for uploads [you]
- Either add Laravel Cloud's **Object Storage** to the project, or use **Cloudflare R2 / S3**.
- Set `FILESYSTEM_DISK=s3` + the bucket/keys (see `.env.production.example`). Region
  `ca-central-1` if you use AWS. Uploads (vendor media, website photos, blog images,
  brochures) must NOT use local disk.

## 5. Environment variables [you — paste secrets]
In the environment's **Variables** editor, paste from `.env.production.example` and fill:
- `APP_NAME=VowNook`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://vownook.com`
- `APP_KEY` — use the "Generate" button (or `php artisan key:generate --show`)
- Mail (Resend): `MAIL_*` + **`MAIL_CASL_ADDRESS`** (a real mailing address — CASL)
- Storage keys (step 4). Leave Stripe/AI commented until you're ready.

## 6. Deploy commands [auto — set once]
In the environment's **Deploy** settings, set the build/deploy commands:
```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build            # (or: pnpm install --frozen-lockfile && pnpm build)
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
> First deploy only: also run `php artisan db:seed --force` once via the project's Commands
> console (seeds the blog + launch promo code + demo data — skip or trim if you don't want demo content).

## 7. Queue worker + scheduler [you — two toggles] ⚠️ REQUIRED
Without these, **no emails send and none of the lifecycle/marketing/post-wedding jobs run.**
- **Workers** → add a worker: `php artisan queue:work --tries=3 --timeout=120`.
- **Scheduler** → enable (runs `php artisan schedule:run` every minute → the 7 jobs).

## 8. Custom domain + SSL [you, ~10 min]
1. Environment → **Domains** → add `vownook.com`. Laravel Cloud shows the DNS records.
2. At your registrar, add those records (and grab `vownook.ca` → 301 to .com).
3. SSL is issued automatically once DNS resolves.

## 9. Smoke test on production [you, 10 min]
- Home loads over https; `<title>` ends "— VowNook"; `/sitemap.xml` + `/robots.txt` resolve.
- Couple signup → add a guest → publish a wedding website (image lands in storage).
- Vendor signup → build a listing (multi-photo + alt + video + brochure) → submit → approve
  as admin → it shows on `/marketplace` + the category page.
- Trigger an email (an inquiry) → confirm delivery via Resend.
- Project Commands: `php artisan schedule:list` shows 7 jobs.

## 10. After launch
- **Search Console + Bing Webmaster Tools**: verify domain, submit `/sitemap.xml`.
- **Sentry** for errors; confirm Laravel Cloud's **automatic Postgres backups** are on.
- **Resend**: add SPF + DKIM DNS records so mail isn't flagged as spam.
- **2FA** on your admin account (built in via Fortify).
- **Stripe**: test-mode pass first (card 4242…), then live keys + register the webhook at
  `https://vownook.com/stripe/webhook`.
- **Enable SSR** (`INERTIA_SSR_ENABLED=true` + an SSR worker) — see §11. Without it, AI crawlers
  see an empty page body.
- **Seed the local SEO guides** (once per deploy that adds cities/categories):
  `php artisan db:seed --class="Database\Seeders\LocalContentSeeder" --force` (idempotent, no API key).

## 11. Enable SSR (server-rendered page bodies) ⚠️ RECOMMENDED

The SEO `<head>` (titles, meta, canonical, JSON-LD) is server-rendered either way. SSR adds the
**page body**, which matters more than it sounds: **AI crawlers (GPTBot, ClaudeBot,
PerplexityBot) execute no JavaScript.** Without SSR they receive an empty `<div id="app"></div>`
and can read nothing but your head schema — and `robots.txt` explicitly invites those bots, so
leaving SSR off wastes the invitation.

> SSR was previously **broken, not merely off**: `app.tsx` is the shared client+SSR entry
> (`@inertiajs/vite` swaps `createInertiaApp` for `createServer`), and its top-level client-only
> code ran in Node, crashing the bundle on boot with `ReferenceError: window is not defined`.
> Fixed in `a96c9e9` by guarding that block behind `typeof window !== 'undefined'`; verified
> rendering locally end-to-end.

To turn it on in Laravel Cloud:

1. **Build command** → no change needed. `pnpm run build` already runs
   `vite build && vite build --ssr` (`build` and `build:ssr` are identical in `package.json`),
   so `bootstrap/ssr/app.js` is produced on every deploy.
2. **Env vars** → add:
   ```
   INERTIA_SSR_ENABLED=true
   INERTIA_SSR_THROW=false
   ```
   (`THROW=false` = a page that errors under SSR silently falls back to client rendering.)
3. **SSR process** → add a Worker/daemon running **`php artisan inertia:start-ssr`** (a
   long-running Node process on port 13714).
   ⚠️ It **refuses to start unless `INERTIA_SSR_ENABLED=true` is already set** — the flag gates
   the command itself, not just per-request dispatch. Set the env var *first*, then start it.
4. **Deploy**, then confirm the body actually renders for a non-JS crawler:
   ```bash
   curl -s -A "GPTBot" https://vownook.com/wedding-photographers/toronto | grep -c '<h1'
   ```
   Expect `≥ 1`. If it's `0`, `#app` is still empty — check the SSR worker is running.

Trade-off: one extra long-running process. Worth it — this is the main GEO/AI-citation lever.

---

### What only you can do (I can't)
Create the Laravel Cloud account, enter payment, paste API keys/passwords, and add DNS
records at your registrar. Everything else (code, migrations, build, deploy commands, env
template) is ready in this repo.
