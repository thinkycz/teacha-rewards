# Teacha Rewards

A mobile-first cashback loyalty wallet for a matcha / bubble tea
store. Customers open a wallet with just a phone number, get a
QR-code to scan at the till, and earn credits every time they buy.
Staff use a separate password-protected surface to log purchases,
redeem rewards, and adjust balances by hand. The whole thing ships
as a PWA so customers can add it to their home screen.

Built on Laravel 13 + Inertia 3 + Vue 3 + TypeScript + Tailwind 4,
using the local `thinkycz/laravel-core` package for framework
helpers, the `database_token` guard, and the validity / thrower
patterns.

## What's in the box

- **Customer flow** (mobile-first responsive; mobile signup with phone number, QR + barcode to scan at the till): `/`, `/wallet`, `/w/{token}`, `/w/{token}/activity`, `/offline`, `/install`
- **Admin / dashboard** (desktop-first sidebar nav, used by staff on a real keyboard at the till): `/dashboard`, `/dashboard/scan`, `/dashboard/wallets`, `/dashboard/transactions`, `/dashboard/settings`, `/dashboard/store-qr`
- **PWA**: branded manifest, offline-first service worker, an
  install banner that handles both Chromium (`beforeinstallprompt`)
  and iOS Safari (manual 3-step guide), branded icons.
- **Services**: `SettingsService`, `RewardWalletService`,
  `RewardTransactionService` — all singletons with the row-lock +
  BigDecimal money pattern documented in
  [docs/cashback-calculation.md](docs/cashback-calculation.md).
- **Seeder**: `TeachaRewardsSeeder` ships admin, staff, 5 wallets,
  20 transactions, and the 4 default settings.

## Getting started

```sh
# 1. Install
composer install
npm install
cp .env.example .env
php artisan key:generate

# 2. Database (uses the sqlite in .env by default)
php artisan migrate --seed

# 3. Run
composer run dev
```

### Local dev over plain `http://`

The default `SESSION_SECURE_COOKIE` derives from the `APP_URL` scheme.
If `APP_URL=http://...` (the default in `.env.example`) the session +
XSRF cookies are **not** marked `Secure`, so the browser stores and
sends them on `http://` — the typical Herd / Valet dev setup.

If you see `TokenMismatchException` on POST forms after a fresh
browser session, check:

1. The browser has an `XSRF-TOKEN` cookie (DevTools → Application →
   Cookies). If not, the session cookie was rejected because it was
   `Secure` over `http://` — flip `SESSION_SECURE_COOKIE=false` in
   `.env` or set `APP_URL` to your `http://` origin.
2. You're not running the Inertia client from a different origin than
   the session cookie was set on.

The seeder creates:

- `admin@teacha.cz` / `password` — admin
- `staff@teacha.cz` / `password` — staff
- 5 customer wallets (Czech names, E.164 phones); the 5th is
  pre-disabled to exercise that path in tests / manual review
- 20 ledger rows (12 purchases, 5 redeems, 2 manual credits, 1
  manual debit)
- The 4 default settings: `cashback_rate=10`, `currency=CZK`,
  `program_name=Teacha Rewards`, `store_name=Teacha`

Re-running the seeder is a no-op once the `teacha_seeder_v1`
sentinel setting exists. To re-seed, `php artisan migrate:fresh
--seed`.

## Quality gates

```sh
make fix          # pint, prettier
make check        # phpstan (max), prettier check, vitest, pest, vue-tsc, vite build
APP_ENV=testing php artisan test       # the pest suite
npm run type-check
```

`make check` runs every verification layer the project ships with.
PHPStan is at `level: max` with `treatPhpDocTypesAsCertain: true`;
no baseline is allowed. New code must not introduce `env()` calls
outside `config/`, must not add `float` columns, and must not relax
PHPStan strictness.

The full pest suite is **125 passing** at the time of this writing
(49 service unit tests, 49 staff + customer feature tests, 5
middleware tests, 5 PWA tests, 2 i18n-parity tests, 2 seeder
tests, plus a small number of pre-existing tests that are not
part of the rewards domain).

## Architecture cheat sheet

- `app/Models/RewardWallet.php` — 32-char URL-safe `public_token`,
  E.164 `phone_normalized` (unique), `wallet_number` like
  `T-XXXX-XXXX`, `decimal:2` cast on every money column.
- `app/Models/RewardTransaction.php` — `uuid` for the public
  reference, four ledger types (`purchase_cashback`, `redeem`,
  `manual_add`, `manual_subtract`, `manual_set`), `metadata` JSON
  column for future extensibility.
- `app/Models/Setting.php` — simple key/value store.
- `app/Services/...` — three singletons registered in
  `RewardServiceProvider`. All money is `BigDecimal`; all writes
  are `DB::transaction` + `lockForUpdate` + BigDecimal math.
- `app/Http/Controllers/Web/...` — Inertia web controllers.
  `app/Http/Controllers/Api/...` — minimal auth-compat endpoints
  (not used by the rewards flow).
- `app/Http/Middleware/EnsureStaffRole.php` +
  `EnsureAdminRole.php` — guard the `/staff/*` namespace.
- `app/Validation/Web/...` — `*Validity` classes for every form.
  Phone uses `propaganistas/laravel-phone`. Money is
  `numeric(null, 0) + decimal(0, 2) + min(...) + max(...)`.
- `resources/js/pages/...` — Vue 3 `<script setup lang="ts">`
  pages, locale-aware via `vue-i18n`. Two layouts:
  `MarketingLayout` (public, mobile-first) and `StaffLayout`
  (sticky topbar + bottom tab nav).
- `resources/js/components/reward/...` — the customer-facing
  primitives: `WalletCard`, `RewardsBalance`, `TransactionList`,
  `TransactionItem`, `PhoneInput`, `QRCodeBlock`.
- `public/manifest.json`, `public/sw.js`, `public/offline.html`,
  `public/icons/...` — the PWA shell. Service worker is registered
  in `resources/js/pwa.ts`; the install banner is mounted
  globally from `app.ts`.

## Cashback math

The full description (formula, rounding, examples, lifecycle,
edge cases) is in [docs/cashback-calculation.md](docs/cashback-calculation.md).
The TL;DR: `cashback = purchaseAmount × cashbackRate / 100`,
rounded to 2 decimal places with `HalfUp`. The rate is **snapshotted
on the transaction** so changing the program rate later doesn't
rewrite history.

## Printing the store QR

Staff (any role) can visit `/staff/store-qr` for a clean,
print-friendly render of a single QR code that points at
`{origin}/wallet`. The page has a print button that calls
`window.print()` and a print stylesheet that hides navigation
chrome. The QR is rendered client-side with the `qrcode` npm
package, so the printout is self-contained.

## PWA install

Two paths depending on the platform:

- **Chromium / Android** — the service worker fires
  `beforeinstallprompt`; the bottom-of-screen banner captures it
  and surfaces a "Přidat na plochu" CTA that calls the native
  install dialog.
- **iOS Safari** — Safari doesn't fire that event, so the banner
  shows the 3-step manual guide (Share → Add to Home Screen → Add).
  A permanent `/install` route (linked from the marketing page)
  has the same steps for users who dismissed the banner.

A service worker (`/sw.js`) precaches the offline shell on install
and uses network-first for navigations with `/offline` as a
fallback. Static assets use stale-while-revalidate so the brand
shell loads instantly on repeat visits.

## Internationalization

Three locales: `en`, `cs` (default), `sk`. Locales live in:

- `lang/{en,cs,sk}.json` — Laravel-flavored keys (validation
  messages, auth strings, etc.)
- `lang/{en,cs,sk}/validation.php` — Laravel validation overrides
- `resources/js/i18n/{en,cs,sk}.json` — Vue messages (wallet,
  marketing, pwa, staff.\*)

The `tests/Unit/I18nParityTest.php` test enforces that the three
Vue message trees have the same top-level keys (so a missing
translation fails CI).

## Defaults

| Setting             | Default                        | Where to change               |
| ------------------- | ------------------------------ | ----------------------------- |
| `cashback_rate`     | `10`                           | `/dashboard/settings`         |
| `currency`          | `CZK`                          | `/dashboard/settings`         |
| `program_name`      | `Teacha Rewards`               | `/dashboard/settings`         |
| `store_name`        | `Teacha`                       | `/dashboard/settings`         |
| Default app locale  | `cs`                           | `.env` `APP_LOCALE`           |
| Wallet public token | 32-char URL-safe (Str::random) | `app/Models/RewardWallet.php` |

## Routes

### Public

- `GET /` — marketing page
- `GET /wallet` — open or create a wallet
- `POST /wallet` — find or create by phone + first name
- `GET /w/{token}` — customer wallet view (QR + balance + history)
- `GET /w/{token}/activity` — full ledger
- `GET /offline` — PWA offline fallback
- `GET /install` — PWA install guide (iOS-friendly steps)

### Admin / dashboard (`/dashboard/*`, requires `staff` or `admin`)

- `GET /dashboard` — stats (active/disabled wallets, today's purchases + cashback) + recent activity + quick actions
- `GET /dashboard/scan` — camera scanner + manual token entry
- `GET /dashboard/scan/{token}` — wallet summary for the scanned token
- `GET /dashboard/wallets` — searchable, filterable, sortable wallet list
- `GET /dashboard/wallets/{wallet}` — full wallet detail + all action panels
- `POST /dashboard/wallets/{wallet}/purchase` — log a purchase → credit cashback
- `POST /dashboard/wallets/{wallet}/redeem` — redeem rewards (must not exceed balance)
- `POST /dashboard/wallets/{wallet}/adjust` — manual add / subtract / set (note required)
- `POST /dashboard/wallets/{wallet}/disable` / `enable` — toggle wallet status
- `GET /dashboard/transactions` — full ledger with type + search filters
- `GET /dashboard/store-qr` — printable store QR sheet (cashier can print and stick at the till)

### Admin-only sub-tree (`/dashboard/settings/*`, requires `admin`)

- `GET /dashboard/settings` — program + store settings (cashback rate, currency, program name, store name)
- `POST /dashboard/settings` — save them
- `/dashboard/store-qr` is also linked from the settings page for convenience

### Auth (unchanged from the boilerplate)

- `GET /login`, `POST /login`, `POST /logout`
- `GET /register`, `POST /register`
- `GET /forgot-password`, `POST /forgot-password`
- `GET /reset-password/{token}`, `POST /reset-password`
- `GET /settings` (profile + password for the logged-in user)

## Manual end-to-end

1. `composer run dev` and open `http://localhost:8000`.
2. From the marketing page, click "Vytvořit nebo otevřít moji peněženku".
3. Enter `+420 600 000 001` + a first name → wallet opens with 0 Kč
   balance and a personal QR + barcode.
4. Sign in as `staff@teacha.cz` / `password`. You land on
   `/dashboard`.
5. Click "Scan" in the sidebar → either scan the customer's QR
   (or barcode at the till scanner) or use the manual
   "Zadejte token ručně" field.
6. On the wallet summary, open "Log a purchase", enter `100 Kč`,
   submit. The wallet now has 10 Kč of rewards (default 10% rate).
7. Open "Redeem rewards", enter `5`, submit. Balance is now 5 Kč.
8. Open "Manual adjustment", pick "Add", enter `20` with note
   "Welcome gift", submit. Balance is 25 Kč.
9. As `admin@teacha.cz`, visit `/dashboard/settings`, change the
   cashback rate to 15, save. A new purchase of 100 Kč now
   credits 15 Kč of rewards; old transactions keep the rate
   snapshot.
10. Visit `/dashboard/store-qr`, click "Print". A printable A4
    sheet with a single QR pointing at `/wallet` is ready to
    stick at the till.

## Where to look in the code

| Want to understand…                | File                                                              |
| ---------------------------------- | ----------------------------------------------------------------- |
| Cashback math, rounding, locking   | `app/Services/Reward/RewardTransactionService.php`                |
| Wallet public token + phone lookup | `app/Services/Reward/RewardWalletService.php`                     |
| Settings read / write              | `app/Services/Settings/SettingsService.php`                       |
| Staff form validation              | `app/Validation/Web/Staff/*Validity.php`                          |
| Customer form validation           | `app/Validation/Web/Wallet/StoreWalletValidity.php`               |
| Service container wiring           | `app/Providers/RewardServiceProvider.php`                         |
| Routes                             | `routes/web.php`                                                  |
| Staff auth + role guard            | `app/Http/Middleware/EnsureStaffRole.php` + `EnsureAdminRole.php` |
| Desktop admin layout               | `resources/js/layouts/AdminLayout.vue`                            |
| Mobile customer layout             | `resources/js/pages/Wallet/Show.vue` (inline)                     |
| PWA install banner                 | `resources/js/components/pwa/PwaInstallBanner.vue`                |
| Customer barcode renderer          | `resources/js/components/reward/BarcodeBlock.vue`                 |
| Service worker                     | `public/sw.js`                                                    |
| Seeder                             | `database/seeders/TeachaRewardsSeeder.php`                        |
| Cashback docs                      | `docs/cashback-calculation.md`                                    |
| Plan, spec, verification           | `docs/`                                                           |

## Conventions

These are the rules the project follows (also captured in
`AGENTS.md`):

- PHP: import every class/interface/trait/enum via `use`. No inline
  FQCNs. Never use `env()` outside `config/`. Money is `BigDecimal`,
  stored as `decimal:N`. Relations are accessed through typed
  relation getters (`getStore()`, `getMovementItems()`) — never
  `$model->relation`. Eloquent scopes are called explicitly
  (`Model::scopeSearch($query, $search)`), never via the magic
  `$query->search()`. Validity classes have a `*Validity` suffix.
  Controllers have a `*Controller` suffix. Enums end in `Enum` and
  expose `public static function values(): array`. Validation
  errors are thrown via `Thrower::default()->message(...)->throw()`,
  not `ValidationException::withMessages()`. Success / error
  flashes use `Inertia::flash(...)` so they survive 302 redirects.
- TS: `noUnusedLocals` and `noUnusedParameters` are on. `useI18n`
  for translations. `Head`, `Link`, `useForm`, `router` from
  `@inertiajs/vue3`. Props typed with `defineProps<{...}>()`.
- Tests: feature tests use `APP_ENV=testing` (CSRF-safe runner from
  commit `bd37c5a`). New controllers get a feature test next to
  them under `tests/Feature/App/Http/Controllers/Web/...`. New
  service methods get unit tests under `tests/Unit/Services/...`.
  Locale additions need an i18n parity test (the parity test will
  fail if `en`/`cs`/`sk` drift).

## License

Proprietary — see the Teacha Rewards project charter.
