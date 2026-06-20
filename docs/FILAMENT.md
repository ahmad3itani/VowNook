# Filament admin (database) panel

A second, auto-generated admin panel — **Filament v5** — for direct CRUD over the
database. It complements the tailored Inertia admin console at `/admin/*` (which
stays the day-to-day tool for support, impersonation, activity, tickets).

## Where it lives

- **URL:** `https://vownook.com/manage` (login at `/manage/login`).
  Mounted at `/manage` so it never collides with the Inertia console at `/admin`.
- **Access:** platform admins only. Gated by `User::canAccessPanel()` =
  `is_admin && ! suspended`. Non-admins get a 403; guests are sent to login.
- **Auth:** the existing `web` guard + `users` table — admins sign in with their
  normal email + password (no separate account).

## What it manages

Auto-generated resources (list / create / edit / delete) for the core tables:
**Users, Weddings, Vendor profiles, Bookings, Payments, Support tickets, Blog
posts, Inquiries, Promo codes.** Each form + table is introspected from the
schema. Add more anytime:

```bash
php artisan make:filament-resource <Model> --generate
```

The **User** resource is hardened: the password is only required on create
(blank-on-edit keeps the current one) and is hashed by the model cast; the 2FA
secrets and the JSON `email_preferences` are not editable here.

## Deploy

- **Assets** publish to `public/{css,js,fonts}/filament` and are **gitignored**;
  they are regenerated on every `composer install` via the `filament:upgrade`
  script wired into `post-autoload-dump`. Laravel Cloud runs `composer install`
  on deploy, so nothing extra is needed.
- **No new env vars.**
- Optional production speed-up: `php artisan filament:optimize` (caches
  components + icons). Clear with `php artisan filament:optimize-clear`.

## Upgrading Filament

`composer update "filament/*" -W` then `php artisan filament:upgrade`
(the latter also re-publishes assets). Review the v5 upgrade notes for breaking
changes before bumping a major.
