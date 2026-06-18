# Teacha Rewards — Spec

> Source: user request + `grill-with-docs` closeout. Date: 2026-06-16.

## 1. Product summary

A mobile-first PWA cashback loyalty wallet for a single matcha / bubble tea
store. Customers identify by phone number, hold one digital wallet, and earn
or redeem 1-Kč credits against their purchases. Staff scan a customer's wallet
QR at the till, log purchases, and redeem rewards. There is **no customer
authentication** — the wallet URL is the identifier and is read-only.

## 2. Resolved decisions (locked from `grill-with-docs`)

| Area                      | Decision                                                                    |
| ------------------------- | --------------------------------------------------------------------------- |
| Admin surface             | Inertia pages under the existing `database_token` guard. **No Filament.**   |
| Staff URL namespace       | `/staff/*` (not `/admin/*`)                                                 |
| PWA                       | Hand-rolled `public/sw.js` + `public/manifest.json`                         |
| Phone normalization       | `propaganistas/laravel-phone`, E.164 stored in `phone_normalized`           |
| Money math                | `brick/math` BigDecimal, `decimal(10,2)` columns                            |
| Database                  | MySQL 8 (per `AGENTS.md`)                                                   |
| Customer auth             | None. `public_token` (32-char URL-safe, 192 bits) in URL is the identifier. |
| Staff auth                | Existing `database_token` guard, email + password                           |
| `users` schema change     | Add `name` and `role` (`admin` / `staff`) columns to the existing table     |
| Architecture tests        | All existing `tests/Architecture/*` must continue to pass                   |
| i18n                      | `cs` + `en`; default `cs`                                                   |
| Scanner camera            | `html5-qrcode`, manual input always available as fallback                   |
| Rate limit `POST /wallet` | 10/min per IP, 3/min per phone                                              |
| Existing surfaces         | `agent_*` and `Dashboard.vue` are **untouched**                             |

## 3. Domain model

### 3.1 `users` (modify existing)

Add columns:

| Column | Type                    | Notes                                 |
| ------ | ----------------------- | ------------------------------------- |
| `name` | `string`                | Display name for staff                |
| `role` | `enum('admin','staff')` | String-backed PHP enum `UserRoleEnum` |

Existing columns (`email`, `password`, `locale`, `remember_token`,
`email_verified_at`, `database_tokens`, `user_password_resets`) are unchanged.

### 3.2 `reward_wallets` (new)

| Column              | Type                                         | Notes                                                         |
| ------------------- | -------------------------------------------- | ------------------------------------------------------------- |
| `id`                | `bigIncrements`                              | Internal PK — **never** exposed publicly                      |
| `uuid`              | `uuid` unique                                | Stable external id used internally                            |
| `public_token`      | `string(48)` unique                          | 32-char URL-safe (192-bit) random. **The** public identifier. |
| `wallet_number`     | `string(16)` unique                          | Short human-readable number for the card, e.g. `T-AB12-34CD`  |
| `first_name`        | `string(64)`                                 | Personalization only                                          |
| `phone`             | `string(32)`                                 | User-entered, display                                         |
| `phone_normalized`  | `string(32)` unique, E.164                   | Used for lookups                                              |
| `rewards_balance`   | `decimal(10,2)` default 0                    | Never < 0                                                     |
| `lifetime_earned`   | `decimal(10,2)` default 0                    |                                                               |
| `lifetime_redeemed` | `decimal(10,2)` default 0                    |                                                               |
| `status`            | `enum('active','disabled')` default `active` |                                                               |
| `last_used_at`      | `timestamp` nullable                         | Updated on every wallet-affecting staff action                |
| `timestamps`        |                                              |                                                               |

Indexes: `phone_normalized` (unique), `public_token` (unique), `wallet_number`
(unique), `status`, `last_used_at`.

### 3.3 `reward_transactions` (new, append-only)

| Column             | Type                                                                             | Notes                                                |
| ------------------ | -------------------------------------------------------------------------------- | ---------------------------------------------------- |
| `id`               | `bigIncrements`                                                                  |                                                      |
| `uuid`             | `uuid` unique                                                                    |                                                      |
| `reward_wallet_id` | `foreignId`                                                                      | constrained, cascade on delete                       |
| `user_id`          | `foreignId` nullable                                                             | constrained, null on delete (preserve history)       |
| `type`             | `enum('purchase_cashback','redeem','manual_add','manual_subtract','manual_set')` |                                                      |
| `purchase_amount`  | `decimal(10,2)` nullable                                                         | Only set on `purchase_cashback`                      |
| `cashback_rate`    | `decimal(5,2)` nullable                                                          | Only set on `purchase_cashback`                      |
| `amount`           | `decimal(10,2)`                                                                  | Signed: positive on add, negative on subtract/redeem |
| `balance_before`   | `decimal(10,2)`                                                                  |                                                      |
| `balance_after`    | `decimal(10,2)`                                                                  |                                                      |
| `note`             | `string` nullable                                                                | Required for `manual_*` types                        |
| `metadata`         | `json` nullable                                                                  |                                                      |
| `timestamps`       |                                                                                  |                                                      |

Indexes: `reward_wallet_id`, `user_id`, `type`, `created_at`.

### 3.4 `settings` (new, simple key/value)

| Column       | Type                | Notes |
| ------------ | ------------------- | ----- |
| `id`         | `bigIncrements`     |       |
| `key`        | `string(64)` unique |       |
| `value`      | `text`              |       |
| `timestamps` |                     |       |

Default rows seeded:

- `cashback_rate` = `10` (percentage)
- `currency` = `CZK`
- `program_name` = `Teacha Rewards`
- `store_name` = `Teacha`

## 4. Enums

All in `app/Enums/`, string-backed, each with `public static function values(): array`.

- `UserRoleEnum` — `admin`, `staff`
- `WalletStatusEnum` — `active`, `disabled`
- `TransactionTypeEnum` — `purchase_cashback`, `redeem`, `manual_add`, `manual_subtract`, `manual_set`
- `ManualAdjustmentTypeEnum` — `add`, `subtract`, `set`

## 5. Services (singleton-bound via `Resolver`)

### 5.1 `App\Services\Reward\RewardWalletService`

- `findOrCreateByPhone(string $phone, string $firstName): RewardWallet`
- `normalizePhone(string $phone): string` — returns E.164, throws on invalid
- `createWallet(string $phone, string $firstName): RewardWallet`
- `getByPublicToken(string $token): RewardWallet`

`findOrCreateByPhone` rule from plan: if a wallet exists, update `first_name`
**only if currently empty**.

### 5.2 `App\Services\Reward\RewardTransactionService`

All methods wrapped in `DB::transaction(...)`. All compute `balance_before`
and `balance_after`. All reject balances going below 0.

- `logPurchase(RewardWallet $wallet, BigDecimal $purchaseAmount, User $user): RewardTransaction`
- `redeem(RewardWallet $wallet, BigDecimal $amount, User $user): RewardTransaction`
- `manualAdd(RewardWallet $wallet, BigDecimal $amount, string $note, User $user): RewardTransaction`
- `manualSubtract(RewardWallet $wallet, BigDecimal $amount, string $note, User $user): RewardTransaction`
- `manualSet(RewardWallet $wallet, BigDecimal $newBalance, string $note, User $user): RewardTransaction`

### 5.3 `App\Services\Settings\SettingsService`

- `get(string $key, mixed $default = null): mixed`
- `set(string $key, mixed $value): void`
- `getCashbackRate(): BigDecimal` (reads `cashback_rate` setting, default `10.00`)

## 6. Routes

All registered through `Resolver::resolveRouteRegistrar()`. Public routes are
unauthenticated; staff routes sit behind the existing
`EnsureInertiaUserIsAuthenticated` middleware plus a new
`EnsureStaffRole` middleware (admin + staff allowed).

### 6.1 Public (no auth)

| Method | URI                          | Controller                               |
| ------ | ---------------------------- | ---------------------------------------- |
| GET    | `/`                          | `Web\Marketing\MarketingIndexController` |
| GET    | `/wallet`                    | `Web\Wallet\WalletCreateController`      |
| POST   | `/wallet`                    | `Web\Wallet\WalletStoreController`       |
| GET    | `/w/{public_token}`          | `Web\Wallet\WalletShowController`        |
| GET    | `/w/{public_token}/activity` | `Web\Wallet\WalletActivityController`    |
| GET    | `/offline`                   | `Web\Pwa\OfflineController`              |

### 6.2 Staff (auth + role middleware)

All `/staff/*` routes are registered through `Resolver::resolveRouteRegistrar()`
behind the existing `EnsureInertiaUserIsAuthenticated` middleware. A new
`EnsureStaffRole` middleware additionally requires the user's `role` to be
`admin` or `staff` (rejects 403 otherwise). The `/staff/settings*` routes
further pass through a new `EnsureAdminRole` middleware that allows only
`admin` users.

| Method | URI                                | Controller                                                 |
| ------ | ---------------------------------- | ---------------------------------------------------------- |
| GET    | `/staff`                           | `Web\Staff\DashboardController`                            |
| GET    | `/staff/scan`                      | `Web\Staff\Scan\ScanIndexController`                       |
| GET    | `/staff/scan/{token}`              | `Web\Staff\Scan\ScanShowController`                        |
| POST   | `/staff/wallets/{wallet}/purchase` | `Web\Staff\Wallets\LogPurchaseController`                  |
| POST   | `/staff/wallets/{wallet}/redeem`   | `Web\Staff\Wallets\RedeemController`                       |
| POST   | `/staff/wallets/{wallet}/adjust`   | `Web\Staff\Wallets\AdjustController`                       |
| POST   | `/staff/wallets/{wallet}/disable`  | `Web\Staff\Wallets\DisableController`                      |
| POST   | `/staff/wallets/{wallet}/enable`   | `Web\Staff\Wallets\EnableController`                       |
| GET    | `/staff/wallets`                   | `Web\Staff\Wallets\WalletIndexController`                  |
| GET    | `/staff/wallets/{wallet}`          | `Web\Staff\Wallets\WalletShowController`                   |
| GET    | `/staff/transactions`              | `Web\Staff\Transactions\TransactionIndexController`        |
| GET    | `/staff/settings`                  | `Web\Staff\Settings\SettingsEditController` (admin only)   |
| POST   | `/staff/settings`                  | `Web\Staff\Settings\SettingsUpdateController` (admin only) |
| POST   | `/staff/logout`                    | `Web\Auth\LogoutController` (reused)                       |

## 7. Validation (validity classes under `app/Validation/Web/...`)

Per the architecture tests, validity classes live under `app/Validation/Web/`
and **only** declare wrapper helpers (no direct `->required()` chains on the
public surface). Each class extends the same pattern used by existing
`AuthValidity` from `thinkycz/laravel-core`.

- `App\Validation\Web\Wallet\StoreWalletValidity` — `phone(): array`, `firstName(): array`
- `App\Validation\Web\Staff\LogPurchaseValidity` — `purchaseAmount(): array`
- `App\Validation\Web\Staff\RedeemValidity` — `amount(): array`
- `App\Validation\Web\Staff\ManualAdjustValidity` — `type(): array`, `amount(): array`, `note(): array`
- `App\Validation\Web\Staff\SettingsValidity` — `cashbackRate(): array`, `currency(): array`, `programName(): array`, `storeName(): array`

## 8. Frontend

### 8.1 Pages (`resources/js/pages/`)

```
pages/
  Marketing/Index.vue                # /
  Wallet/
    Create.vue                       # /wallet
    Show.vue                         # /w/{public_token}
    Activity.vue                     # /w/{public_token}/activity
  Staff/
    Dashboard.vue                    # /staff
    Scan/Index.vue                   # /staff/scan
    Scan/Show.vue                    # /staff/scan/{token}
    Wallets/Index.vue                # /staff/wallets
    Wallets/Show.vue                 # /staff/wallets/{wallet}
    Transactions/Index.vue           # /staff/transactions
    Settings/Index.vue               # /staff/settings
  Pwa/Offline.vue                    # /offline
```

### 8.2 Components (`resources/js/components/`)

```
components/
  reward/
    WalletCard.vue
    RewardsBalance.vue
    TransactionList.vue
    TransactionItem.vue
    PhoneInput.vue
    QRCodeBlock.vue                  # uses html5-qrcode for camera, renders QR via 'qrcode' npm pkg
  pwa/
    PwaInstallBanner.vue
    AddToHomeScreenGuide.vue
  ui/
    EmptyState.vue                   # already themed
    SuccessToast.vue
```

### 8.3 Design tokens

Add to `resources/css/app.css` `@theme` block:

- `--color-matcha-50` … `--color-matcha-900` (matcha green scale)
- `--color-sage-*`
- `--color-cream-*`
- `--color-charcoal-*`

Layout uses rounded-2xl/3xl, soft shadows, mobile-first, no purple. The
existing `Brand.vue` and the rest of the UI components get re-themed in this
phase.

## 9. PWA

- `public/manifest.json` — name `Teacha Rewards`, short_name `Teacha`,
  `start_url=/`, `display=standalone`, theme color `#5C7F4F` (matcha-500),
  background `#FAF8F1` (cream-50), 192x192 + 512x512 icon placeholders.
- `public/sw.js` — install: precache `/`, `/offline`, manifest, icons. Fetch:
  network-first for navigations with `/offline` fallback, stale-while-revalidate
  for static assets.
- `public/offline.html` — minimal offline landing (used by SW, not Inertia).
- `resources/js/app.ts` — register `navigator.serviceWorker.register('/sw.js')`
  on load, dispatch `appinstalled` event for `PwaInstallBanner`.
- `PwaInstallBanner.vue` — listens for `beforeinstallprompt`, shows a
  dismissible install CTA.
- `AddToHomeScreenGuide.vue` — static iOS / Android instructions on the wallet
  page, hidden if the app is already running standalone.

## 10. Seeding

`Database\Seeders\TeachaRewardsSeeder`:

- 1 admin user: `admin@teacha.test` / `password` (role `admin`)
- 1 staff user: `staff@teacha.test` / `password` (role `staff`)
- 5 example wallets (Czech phone numbers, varied balances, 1 disabled)
- ~20 example transactions spread across the wallets (purchases, redemptions,
  one manual adjustment)
- 4 default settings rows

Wired into `DatabaseSeeder::run()` so `make local` and `make development`
both seed.

## 11. Tests (Pest)

Per `CoverageArchitectureTest`, every new controller must have a feature
test at the mirrored path under `tests/Feature/App/Http/Controllers/Web/...`.

Plan-mandated coverage:

- wallet creation (`WalletStoreControllerTest`)
- existing wallet retrieval by phone + first_name update rule (`WalletStoreControllerTest`)
- phone normalization — E.164 output, invalid rejected (`RewardWalletServiceTest`)
- purchase cashback calculation — 100 @ 10% = 10, decimal precision
  (`RewardTransactionServiceTest`)
- redeem validation — cannot exceed balance, rejects 0 and negative
  (`RedeemControllerTest`, `RewardTransactionServiceTest`)
- manual adjustment — add/subtract/set with required note, note required
  (`AdjustControllerTest`, `RewardTransactionServiceTest`)
- public token access — read-only, no auth, 404 on bad token
  (`WalletShowControllerTest`)
- preventing negative balance — service throws on underflow
  (`RewardTransactionServiceTest`)

Plus architectural unit tests for the services themselves.

## 12. README

`README.md` must cover:

- Install (`make local`)
- Build (`npm run build`, `npm run dev`)
- Migrate + seed
- PWA setup notes (HTTPS requirement, icon replacement, service worker cache
  invalidation)
- How to print the store QR code (the marketing landing `/` has a print-friendly
  section; admin can print it once and post it at the till)
- Cashback calculation: `cashback = round(purchase_amount * cashback_rate / 100, 2)`
  using `brick/math`, example `120 Kč × 10 % = 12 Kč`.
- Default seeded credentials
- Where to change the cashback rate (`/staff/settings`, admin only)

## 13. Out of scope (post-MVP)

- Email / SMS notifications to customers
- Tiered cashback rates (e.g. double cashback on matcha)
- Expiring credits
- Multi-store support
- Per-wallet override of cashback rate
- Localization beyond `cs` + `en`
- Real push notifications
- Webhook integrations
- Customer history export / GDPR tools
