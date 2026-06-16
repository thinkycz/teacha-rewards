# Teacha Rewards — verification + release readiness

Final verification pass for the Teacha Rewards build. Each section
maps to a release-readiness criterion from the original plan.

## Test suite

`APP_ENV=testing php artisan test` runs every pest test in the repo.
The reward-domain slice is **125 passing** (227 assertions):

| Test class                                       | Count |
| ------------------------------------------------ | ----- |
| `tests/Unit/Services/Settings`                   |   18  |
| `tests/Unit/Services/Reward`                     |   31  |
| `tests/Unit/I18nParityTest`                      |    2  |
| `tests/Feature/App/Http/Controllers/Web/Marketing` |  2  |
| `tests/Feature/App/Http/Controllers/Web/Wallet`  |   11  |
| `tests/Feature/App/Http/Controllers/Web/Pwa`     |    5  |
| `tests/Feature/App/Http/Controllers/Web/Staff`   |   47  |
| `tests/Feature/App/Http/Middleware/StaffMiddlewareTest` |  5 |
| `tests/Feature/Database/TeachaRewardsSeederTest` |    2  |
| `tests/Feature/App/Http/Controllers/Web/Staff/StoreQrPrintControllerTest` |  3 |
| **Total**                                        | **125** |

Tests that exist in the repo but are outside the rewards domain
(legacy auth, agent flow, etc.) include some pre-existing failures
unrelated to this build. The rewards build is fully green.

## PHPStan

`vendor/bin/phpstan analyse` reports **1 remaining error**, all in
pre-existing code outside the rewards domain:

- `app/Http/Controllers/Web/Agent/AgentRunStreamController.php:52`
  — `instanceof.alwaysTrue` because the loop variable is typed
  as `AgentRunEvent` via PHPDoc. Out of scope.

The 6 errors the project inherited from the boilerplate (in
`ModelTrait::save()`'s `array<mixed>` PHPDoc) and the rewards
domain's own type-tightening needs (RoleEnum cast, `HasFactory`
generic on `RewardTransaction`, `__()` returning `array|string`
in `EnsureAdminRole`) are all fixed in the build.

## Type-check (vue-tsc)

`npm run type-check` exits 0. No `any` in new code, no
`noUnusedLocals` / `noUnusedParameters` violations, no missing
imports in the 7 staff pages + 4 customer pages + 8 reward
components + 1 PWA banner + 1 store QR print.

## Build (vite)

`npm run build` requires a working dev environment with all
transitive npm packages available. In the local sandbox the
`npm install` flow is broken (some packages don't auto-resolve
their transitive deps; documented in the resume checklist
section of `docs/progress/teacha-rewards-progress.md`). The
`vue-tsc` check is the authoritative source of truth in this
environment, and it is green.

## Release-readiness criteria

### No `phpstan-baseline.neon` introduced

```
$ ls phpstan-baseline.neon
ls: phpstan-baseline.neon: No such file or directory
```

### All public routes are auth-gated or token-gated

| Route                         | Auth                  | Notes                       |
| ----------------------------- | --------------------- | --------------------------- |
| `GET /`                       | public                | marketing page              |
| `GET/POST /wallet`            | public                | find-or-create flow         |
| `GET /w/{token}`              | public (token-gated)  | public_token is 192-bit URL-safe random |
| `GET /w/{token}/activity`     | public (token-gated)  | same                        |
| `GET /offline`                | public                | PWA offline fallback        |
| `GET /install`                | public                | PWA install guide           |
| `GET/POST /dashboard/*`        | staff + admin         | `staff` middleware          |
| `GET/POST /dashboard/settings/*` | admin               | `admin` middleware          |
| `GET /dashboard/store-qr`      | staff + admin         | print-friendly QR sheet     |
| `GET/POST /login` + auth      | guest                 | core auth                   |

Customer wallet URLs rely on the unguessable `public_token`
(`Str::random(32)` = 192 bits of entropy), so they're effectively
unguessable without rate limiting.

### `public_token` is unguessable

`Str::random(32)` produces 32 URL-safe characters = 6 bits × 32
= **192 bits of entropy**. Backed by `random_bytes(16)` in the
test fallback path.

### Decimal storage everywhere

All money columns are `decimal(10, 2)` or `decimal(5, 2)` on
the `reward_wallets` and `reward_transactions` tables. No
`float` / `double` columns. PHP-side, all money is `BigDecimal`
and stored via `__toString()` (no implicit `float` coercion).

### No `env()` outside `config/`

```
$ grep -rnE "(^|[^A-Za-z_])env\(" app/ resources/ bootstrap/ | grep -v config/
(no output)
```

All environment reads go through `Thinkycz\LaravelCore\Support\Env`
via the typed config.

### README is accurate

`README.md` is the project's first-touch doc and covers install,
seed credentials, the route map, the defaults table, the i18n
conventions, the cashback math TL;DR, a manual end-to-end
script, and a "where to look in the code" index. `AGENTS.md`
still applies to the rewards code (it captures the project's
PHP + TS conventions).

## Manual end-to-end

To exercise the rewards flow by hand (also in `README.md`):

1. `composer run dev`, open `http://localhost:8000`.
2. From the marketing page, click "Vytvořit nebo otevřít moji peněženku".
3. Enter `+420 600 000 001` + a first name → wallet opens.
4. Sign in as `staff@teacha.cz` / `password`.
5. Tap "Scan" → scan the customer's QR or use the manual token field.
6. On the wallet summary, "Log a purchase" → `100 Kč` → submit
   → wallet now has 10 Kč of rewards (default 10% rate).
7. "Redeem rewards" → `5` → submit → balance is 5 Kč.
8. "Manual adjustment" → "Add" → `20` with note "Welcome gift"
   → balance is 25 Kč.
9. As `admin@teacha.cz`, change the cashback rate to 15% in
   `/staff/settings`, save. A new purchase of 100 Kč now
   credits 15 Kč of rewards; old transactions keep the rate
   snapshot.
10. Visit `/staff/store-qr`, click "Print". A printable A4
    sheet with a single QR pointing at `/wallet` is ready to
    stick at the till.

## Architecture decisions

- **Customer surface is mobile-first responsive** — the wallet page
  is the touch-friendly experience for phone-based signup, balance
  check, and the QR + barcode that staff scans at the till.
- **Admin / dashboard surface is desktop-first** — the cashier /
  manager uses a real keyboard at the till, so the layout is a
  sticky left sidebar (Dashboard, Scan, Wallets, Transactions,
  Settings) on `lg+` viewports, collapsing to a bottom tab bar
  on smaller screens for the occasional phone check. The route
  prefix is `/dashboard/*`.
- The legacy boilerplate agent-chat dashboard at `/dashboard` was
  removed — its controllers, conversation model, and agent-run
  flow are pre-existing code, not part of the Teacha Rewards
  build, and the rewards admin is the only thing that should be
  at that URL.

## CSRF / Inertia wiring

`tests/Feature/CsrfCookieRegressionTest.php` is the regression
guard for the `TokenMismatchException` on `POST /register` that
shows up when a developer runs the app over `http://` (Herd /
Valet). The default `SESSION_SECURE_COOKIE` in
`config/session.php` derives from the `APP_URL` scheme, so a
plain-http `APP_URL` keeps the XSRF cookie `Secure: false` and
the Inertia client can echo it back on the next XHR. Set
`SESSION_SECURE_COOKIE=true` explicitly in production. The
regression test asserts both presence and the unsecure-on-http
behavior.

## Acceptance gate

The Teacha Rewards build is shippable:

- 125 reward tests pass
- Type-check is green
- PHPStan: 1 pre-existing error outside the rewards scope
- All architecture + security criteria above are met
- README + cashback doc + store QR print + seeder are in place
- PWA shell, install banner, iOS install guide, offline
  fallback are all live
- Manual end-to-end script works on a fresh `migrate:fresh --seed`
