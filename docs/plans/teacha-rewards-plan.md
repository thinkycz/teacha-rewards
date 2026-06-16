# Teacha Rewards — Plan

> Source: `docs/specs/teacha-rewards-spec.md`. Date: 2026-06-16.

## Build order

Phases are designed so each one is independently verifiable, leaves the test
suite green, and adds a meaningful, demoable slice. **Do not start the next
phase until the current one is green** (`make check` passing, new tests
green).

---

## Phase 1 — Domain + database

**Goal:** New schema and Eloquent models are in place with the architecture
tests still green.

### Tickets

1. **T1.1 — Add `name` and `role` to `users`** (modify existing migration)
   - `add_name_and_role_to_users_table` migration
   - Extend `App\Models\User`:
     - `getName(): string`, `getRole(): UserRoleEnum`
     - `isAdmin(): bool`, `isStaff(): bool`
     - `casts()` for `role`
   - Extend `UserFactory` with `name()` and `admin()` / `staff()` states
   - Update `app.locale` in `User` factory default if needed

2. **T1.2 — New enums**
   - `app/Enums/UserRoleEnum.php` (admin, staff)
   - `app/Enums/WalletStatusEnum.php` (active, disabled)
   - `app/Enums/TransactionTypeEnum.php` (5 cases)
   - `app/Enums/ManualAdjustmentTypeEnum.php` (add, subtract, set)
   - Each: string-backed, `public static function values(): array`
   - Passes `tests/Architecture/EnumArchitectureTest.php`

3. **T1.3 — `reward_wallets` migration + model**
   - `create_reward_wallets_table` migration with all columns from spec §3.2
   - `app/Models/RewardWallet.php` extending `BaseModel`
     - `querySelect()`, `scopeSearch()` (search on `first_name`, `phone`, `wallet_number`)
     - `casts()` for `rewards_balance` etc. to `BigDecimal` via `brick/math`
     - `getPublicToken(): string`, `getFirstName(): string`, `getPhone(): string`,
       `getPhoneNormalized(): string`, `getRewardsBalance(): BigDecimal`,
       `getLifetimeEarned(): BigDecimal`, `getLifetimeRedeemed(): BigDecimal`,
       `getStatus(): WalletStatusEnum`, `getLastUsedAt(): Carbon|null`
     - `transactions(): HasMany<RewardTransaction>`
   - `database/factories/RewardWalletFactory.php` with `disabled()` state

4. **T1.4 — `reward_transactions` migration + model**
   - `create_reward_transactions_table` migration with all columns from spec §3.3
   - `app/Models/RewardTransaction.php` extending `BaseModel`
     - `querySelect()`, `scopeSearch()` (search on `note` + wallet first_name via join)
     - `casts()` for amounts, metadata
     - `getType(): TransactionTypeEnum`, `getAmount(): BigDecimal`, etc.
     - `wallet(): BelongsTo<RewardWallet>`, `user(): BelongsTo<User>`

5. **T1.5 — `settings` migration + model**
   - `create_settings_table` migration
   - `app/Models/Setting.php` extending `BaseModel`
     - `querySelect()`, `scopeSearch()` (search on `key`)
     - `getKey(): string`, `getValue(): string`
   - `database/factories/SettingFactory.php`

### Acceptance for Phase 1

- `php artisan migrate:fresh` runs cleanly
- `make check` passes (PHPStan max, architecture tests, lint, type-check, build, vitest, pest)
- All four enums + three new models exist with the getters above

---

## Phase 2 — Services + unit tests

**Goal:** The three service classes are wired, type-safe, and fully unit-tested
with Pest. No HTTP surface yet.

### Tickets

1. **T2.1 — `SettingsService`**
   - `app/Services/Settings/SettingsService.php`
   - Registered as a singleton in a new `app/Providers/RewardServiceProvider.php`
   - **Add `App\Providers\RewardServiceProvider::class` to `bootstrap/providers.php`**
     so `Resolver::resolve(SettingsService::class)` works at runtime
   - `get`, `set`, `getCashbackRate(): BigDecimal` (default 10.00)
   - `tests/Unit/Services/Settings/SettingsServiceTest.php`
     - `get` returns default when missing
     - `set` persists
     - `getCashbackRate` returns `BigDecimal` of `10.00` when unset, otherwise the
       stored value

2. **T2.2 — `RewardWalletService`**
   - `app/Services/Reward/RewardWalletService.php`
   - `normalizePhone` uses `propaganistas/laravel-phone` `AsE164` cast
   - `createWallet` generates `public_token` via `Str::random(32)` (Laravel 13
     draws from the `[A-Za-z0-9]` pool, which is already URL-safe — no extra
     filter step; 192 bits of entropy) and `wallet_number` (formatted
     `T-XXXX-XXXX` from `Str::upper(Str::random(8))`)
   - `findOrCreateByPhone`:
     - normalize
     - lookup by `phone_normalized`
     - on miss, create
     - on hit, update `first_name` **only if currently empty** (string trim + empty check)
   - `getByPublicToken` throws `ModelNotFoundException` on miss
   - `tests/Unit/Services/Reward/RewardWalletServiceTest.php`
     - normalizes `+420 123 456 789` → `+420123456789`
     - rejects `not-a-phone`
     - creates wallet on first call, returns same on second
     - updates first_name only when empty

3. **T2.3 — `RewardTransactionService`**
   - `app/Services/Reward/RewardTransactionService.php`
   - All `logPurchase` / `redeem` / `manual*` methods take a row-level lock
     (`->lockForUpdate()` on the wallet) inside the `DB::transaction(...)`
     to prevent races when two staff act on the same wallet at once
   - `logPurchase`:
     - `cashback = purchaseAmount * cashbackRate / 100` (brick/math, rounded
       to 2 decimal places)
     - inside `DB::transaction`:
       - lock wallet row
       - insert transaction with `type=purchase_cashback`, `purchase_amount`,
         `cashback_rate`, `amount = cashback`, `balance_before`, `balance_after`
       - increment `rewards_balance` and `lifetime_earned` on wallet
       - touch `last_used_at`
   - `redeem`:
     - rejects when `amount > rewards_balance` (Thrower)
     - inserts `type=redeem`, `amount = -amount`, decrements balance,
       increments `lifetime_redeemed`
   - `manualAdd` / `manualSubtract` / `manualSet`:
     - require non-empty `note` (Thrower)
     - prevent negative balance (Thrower)
     - all inside `DB::transaction` with the row lock
   - `tests/Unit/Services/Reward/RewardTransactionServiceTest.php`
     - `logPurchase` 100 @ 10% = 10
     - `redeem` rejects 0, negative, over-balance
     - manual add/subtract/set all update balance + write transaction
     - negative balance prevented
     - transaction row always written (count before == count after + 1)

### Acceptance for Phase 2

- All three services have unit tests covering happy path and each rejection
- `make check` passes

---

## Phase 3 — Public customer flow

**Goal:** A customer can hit `/`, create or open a wallet, and see the wallet
page with mock data. Read-only at this stage — staff actions are next phase.

### Tickets

1. **T3.1 — `EnsureStaffRole` middleware**
   - `app/Http/Middleware/EnsureStaffRole.php`
   - Reject non-staff (401/403) — staff and admin both pass

2. **T3.2 — Public marketing page**
   - `Web\Marketing\MarketingIndexController` → `Inertia::render('Marketing/Index')`
   - Page with hero, "Get rewarded for every matcha.", CTA → `/wallet`
   - Replaces the current `GET /` redirect closure in `routes/web.php`. The
     new behavior is: `GET /` **always** renders the marketing page, even for
     authenticated staff. Staff navigate to `/staff` for their dashboard
     (added in Phase 4).

3. **T3.3 — Wallet create form**
   - `Web\Wallet\WalletCreateController` (GET) → `pages/Wallet/Create.vue`
   - `Web\Wallet\WalletStoreController` (POST)
     - validate via `StoreWalletValidity`
     - throttle: 10/min per IP, 3/min per phone (two `Limit`s, both `hit()`-ed)
     - call `RewardWalletService::findOrCreateByPhone`
     - `Inertia::flash('success', __('Wallet ready.'))`
     - redirect to `/w/{public_token}`

4. **T3.4 — `StoreWalletValidity`**
   - `phone` uses `phone:cz,mobile` rule (propaganistas/laravel-phone)
   - `first_name` uses `name` rule from `thinkycz/laravel-core` `AuthValidity`

5. **T3.5 — Wallet show page**
   - `Web\Wallet\WalletShowController` (GET) → `pages/Wallet/Show.vue`
   - `WalletCard` component shows first name, balance, lifetime stats, wallet
     number, `QRCodeBlock` (the URL `/w/{public_token}`), recent 5
     transactions, `PwaInstallBanner`, `AddToHomeScreenGuide`
   - 404 on bad token (Thrower or explicit `abort(404)`)

6. **T3.6 — Wallet activity page**
   - `Web\Wallet\WalletActivityController` (GET) → `pages/Wallet/Activity.vue`
   - Full paginated transaction list (latest first)

7. **T3.7 — Feature tests**
   - `WalletCreateControllerTest`, `WalletStoreControllerTest` (creation,
     retrieval by phone, first_name update rule, normalization, throttle)
   - `WalletShowControllerTest` (200 with valid token, 404 with bad, no
     auth required)
   - `WalletActivityControllerTest` (lists transactions in reverse order)

### Acceptance for Phase 3

- `php artisan serve` + `npm run dev`: `/` renders the marketing page for
  guests, `/wallet` form works, `/w/{token}` shows the wallet with QR.
- `make check` passes
- The wallet URL alone is enough to view a wallet (no login).

---

## Phase 4 — Staff surface

**Goal:** A staff member can log in, scan a wallet, log a purchase, redeem
rewards, and do a manual adjustment. Admin can edit settings.

### Tickets

1. **T4.1 — Route registration**
   - Add all `/staff/*` routes to `routes/web.php` under
     `EnsureInertiaUserIsAuthenticated` + new `EnsureStaffRole` middleware
   - All via `Resolver::resolveRouteRegistrar()`

2. **T4.2 — Staff dashboard**
   - `Web\Staff\DashboardController` → `pages/Staff/Dashboard.vue`
   - Shows recent transactions, total active wallets, big "Scan wallet" button

3. **T4.3 — Staff wallet list + detail**
   - `Web\Staff\Wallets\WalletIndexController` with `q`, `status`, `sort`
     query params
   - `Web\Staff\Wallets\WalletShowController` shows full wallet with
     transactions, manual adjust form, disable/enable button

4. **T4.4 — Scanner**
   - `Web\Staff\Scan\ScanIndexController` → `pages/Staff/Scan/Index.vue`
   - `Web\Staff\Scan\ScanShowController` resolves token via
     `RewardWalletService::getByPublicToken` (404 on bad), renders
     `pages/Staff/Scan/Show.vue` with the customer summary + Log Purchase /
     Redeem / Adjust buttons

5. **T4.5 — Log purchase, redeem, adjust**
   - `Web\Staff\Wallets\LogPurchaseController` (POST)
     - `LogPurchaseValidity` → `RewardTransactionService::logPurchase`
   - `Web\Staff\Wallets\RedeemController` (POST)
     - `RedeemValidity` → `RewardTransactionService::redeem`
   - `Web\Staff\Wallets\AdjustController` (POST)
     - `ManualAdjustValidity` → dispatches on `type` to one of the three
       `manual*` methods
   - All three redirect back to the scan or wallet show page with
     `Inertia::flash('success', ...)` using translation keys:
     - `reward.purchase_logged` — "Purchase logged. +X Kč added."
     - `reward.redeemed` — "Redeemed X Kč."
     - `reward.adjusted` — "Balance adjusted."
   - Keys live in `lang/cs.json` and `lang/en.json`; their i18n strings
     should be added in this ticket, not deferred.

6. **T4.6 — Disable / enable wallet**
   - `Web\Staff\Wallets\DisableController` and `EnableController`
   - `manualSet`-style transaction with a system note (`"disabled by staff"`)

7. **T4.7 — Transactions list**
   - `Web\Staff\Transactions\TransactionIndexController` (admin + staff)
   - Filter by `type`, `wallet_id`, `user_id`, date range

8. **T4.8 — Settings (admin only)**
   - `EnsureStaffRole` already gates `/staff/*`; add an
     `EnsureAdminRole` middleware for `/staff/settings*`
   - `Web\Staff\Settings\SettingsEditController` shows current values
   - `Web\Staff\Settings\SettingsUpdateController` validates via
     `SettingsValidity` and writes through `SettingsService::set`

9. **T4.9 — Feature tests**
   - `LogPurchaseControllerTest` — 100 @ 10% → +10 balance, transaction
     recorded, flash success
   - `RedeemControllerTest` — over-balance rejected, exactly-balance OK,
     transaction recorded
   - `AdjustControllerTest` — add / subtract / set, note required, negative
     prevented
   - `ScanShowControllerTest` — 404 on bad token
   - `SettingsControllerTest` — admin can edit, staff cannot

### Acceptance for Phase 4

- Full staff flow works end-to-end: login → scan → log purchase → see new
  balance on customer wallet.
- `make check` passes.
- Settings change is reflected in next purchase.

---

## Phase 5 — PWA

**Goal:** The app installs, shows an install banner, and renders an offline
fallback.

### Tickets

0. **T5.0 — Register PWA routes in `routes/web.php`**
   - Add `GET /offline` (and any future PWA shell routes) to
     `routes/web.php` via `Resolver::resolveRouteRegistrar()`
   - The `/offline` route is public (no auth, no staff role) and points at
     `Web\Pwa\OfflineController`
   - Prevents the "controller exists but the route is missing" failure at
     implementation time

1. **T5.1 — `public/manifest.json`**
   - name, short_name, start_url=`/`, display=`standalone`, theme_color
     `#5C7F4F`, background_color `#FAF8F1`, icons 192/512
   - Generated icon placeholders: simple SVG `public/icons/icon.svg`, PNGs via
     `sharp` (one-shot script in `tools/build-pwa-icons.cjs`)

2. **T5.2 — `public/sw.js`**
   - install: precache `['/', '/offline', '/manifest.json', '/icons/icon-192.png', '/icons/icon-512.png']`
   - activate: claim clients, delete old caches
   - fetch: network-first for navigations, fallback to `/offline`; cache-first
     for `/build/*`, `/icons/*`, `/manifest.json`
   - `serviceWorkerVersion` baked in so future deploys can detect update

3. **T5.3 — Service worker registration in `app.ts`**
   - On `load`, `navigator.serviceWorker.register('/sw.js')` with logging on
     `updatefound`

4. **T5.4 — `/offline` route + page**
   - `Web\Pwa\OfflineController` returns the `Pwa/Offline.vue` Inertia page
   - Pairs with `public/offline.html` for SW-only fallback

5. **T5.5 — `PwaInstallBanner.vue`**
   - Listens for `beforeinstallprompt`, shows dismissible banner on the wallet
     page (and marketing page) only when not already installed

6. **T5.6 — `AddToHomeScreenGuide.vue`**
   - Renders iOS Safari and Android Chrome steps; hidden when
     `display-mode: standalone`

### Acceptance for Phase 5

- Lighthouse PWA audit passes
- Manifest validates
- `navigator.serviceWorker.controller` is non-null after first visit
- Disabling network and reloading `/` shows `/offline`

---

## Phase 6 — Seed + design polish

**Goal:** Seeded data, design tokens, README.

### Tickets

1. **T6.1 — `TeachaRewardsSeeder`**
   - 1 admin user: `admin@teacha.test` / `password` (role `admin`)
   - 1 staff user: `staff@teacha.test` / `password` (role `staff`)
   - 5 example wallets (mixed balances, 1 disabled), all with Czech
     `+420` phone numbers
   - **20 example transactions with this fixed distribution** (avoid the
     "20 purchases, 0 redemptions" trap):
     - 3 `purchase_cashback` per active wallet × 4 active wallets = 12
     - 1 `redeem` per active wallet × 4 active wallets = 4
     - 2 `manual_add` across the set (with notes)
     - 1 `manual_subtract` (with note)
     - 1 `manual_set` (with note)
     - Total: 20. The disabled wallet gets 0 transactions.
   - `last_used_at` is set to `created_at` of the latest transaction on
     each wallet
   - 4 default settings rows (`cashback_rate`, `currency`, `program_name`,
     `store_name`)

2. **T6.2 — `DatabaseSeeder`**
   - Call `TeachaRewardsSeeder`

3. **T6.3 — Design tokens**
   - Add matcha / sage / cream / charcoal scales to `resources/css/app.css`
     `@theme` block
   - **Do not** re-theme the existing `components/ui/Brand.vue` — that
     component is rendered by `AppLayout.vue`, which is the layout of
     `Dashboard.vue` and the existing agent pages. The closeout promises
     those are untouched.
   - Instead, add a new `components/reward/RewardBrand.vue` with a matcha
     mark (rounded square, gradient). Use it on the marketing, wallet, and
     staff reward pages. The existing `Brand.vue` continues to render in
     the existing app shell.

4. **T6.4 — Frontend polish pass**
   - `pages/Marketing/Index.vue` — hero, gradient, CTA
   - `WalletCard.vue` — premium card layout, rounded-3xl, soft shadow
   - `PwaInstallBanner.vue` — matcha-themed dismissable toast
   - Use `RewardBrand.vue` (not the existing `Brand.vue`) on the marketing,
     wallet, and staff reward pages

### Acceptance for Phase 6

- `make local` produces a demoable state
- No purple anywhere in the design
- Mobile-first layouts verified at 375px

---

## Phase 7 — Verification + handoff

**Goal:** Every verification layer passes, the project is ready to ship.

### Tickets

1. **T7.1 — `verification-before-completion`**
   - Run `make fix` then `make check`
   - Run `php artisan migrate:fresh --seed`
   - Manually exercise: marketing → wallet create → wallet show → staff
     login → scan → log purchase → redeem → manual adjust → settings
   - Run Playwright happy path if time allows (existing `playwright.config.ts`)

2. **T7.2 — `release-readiness`**
   - Confirm `phpstan-baseline.neon` is **not** introduced
   - Confirm all new public routes are rate-limited or auth-gated
   - Confirm `public_token` is unguessable (32-char URL-safe, 192 bits)
   - Confirm decimal storage everywhere (no `float` columns)
   - Confirm no `env()` outside config
   - Confirm README sections present and accurate

3. **T7.3 — README**
   - Final pass on `README.md` (install, build, seed, PWA, print store QR,
     cashback calc, defaults)

### Acceptance for Phase 7

- All verification layers green
- README is accurate
- Project is shippable
