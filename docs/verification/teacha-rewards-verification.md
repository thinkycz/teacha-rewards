# Teacha Rewards — Verification

> Layers to clear before claiming any phase complete. Source of truth:
> `docs/specs/teacha-rewards-spec.md` and `docs/plans/teacha-rewards-plan.md`.

## Per-phase minimum verification

| Phase | Code | Architecture | Test | Runtime / manual |
|---|---|---|---|---|
| 1 Domain + database | `phpstan analyse` clean | all `tests/Architecture/*` green | `pest` migrations test | `php artisan migrate:fresh` runs |
| 2 Services | `phpstan analyse` clean | unchanged | new unit tests green | n/a |
| 3 Public flow | `phpstan analyse` clean, `npm run type-check` clean | unchanged | per-controller Pest feature tests green | `php artisan serve` + browser: marketing → create → wallet show works, no auth needed for `/w/{token}` |
| 4 Staff surface | `phpstan analyse` clean, `npm run type-check` clean | unchanged | per-controller Pest feature tests green | browser: login → scan (manual token) → log purchase → redeem → adjust → settings; verify balance change on customer page |
| 5 PWA | `npm run build` clean | unchanged | n/a | Lighthouse PWA audit, offline reload shows `/offline`, install banner appears on second visit |
| 6 Seed + design | `phpstan analyse` clean, `npm run type-check` clean, `npm run build` clean | unchanged | existing Pest + new tests still green | `make local` produces demoable state |
| 7 Verification + handoff | `make fix && make check` clean | unchanged | full suite green | end-to-end manual run-through, `release-readiness` skill pass |

## Spec acceptance coverage

Each plan-mandated test from `docs/specs/teacha-rewards-spec.md` §11 must have
a Pest test in the corresponding location:

- [ ] wallet creation — `tests/Feature/.../Wallet/WalletStoreControllerTest.php`
- [ ] existing wallet retrieval by phone + first_name update rule — same file
- [ ] phone normalization — `tests/Unit/Services/Reward/RewardWalletServiceTest.php`
- [ ] purchase cashback calculation — `tests/Unit/Services/Reward/RewardTransactionServiceTest.php`
- [ ] redeem validation — `tests/Unit/Services/Reward/RewardTransactionServiceTest.php` + `tests/Feature/.../Staff/Wallets/RedeemControllerTest.php`
- [ ] manual adjustment (add / subtract / set, note required) — `tests/Unit/.../RewardTransactionServiceTest.php` + `tests/Feature/.../Staff/Wallets/AdjustControllerTest.php`
- [ ] public token access — `tests/Feature/.../Wallet/WalletShowControllerTest.php`
- [ ] preventing negative balance — `tests/Unit/Services/Reward/RewardTransactionServiceTest.php`

## End-to-end manual script (run during Phase 7)

1. `make local`
2. Open `http://localhost:8000` in a mobile-width viewport
3. Click "Create or open my rewards wallet"
4. Enter `+420 123 456 789` and `Anička` → submit
5. Verify you land on `/w/{token}` showing the wallet card, balance `0 Kč`,
   QR code, and PWA install banner
6. Repeat step 4 with the same phone, leave first_name blank → verify the
   same wallet reopens with the existing first_name
7. In a separate incognito window, log in to `/login` as
   `staff@teacha.test` / `password`
8. Navigate to `/staff/scan`, paste the customer's `public_token`, submit
9. On the customer screen, click "Log Purchase", enter `120`, submit
10. Verify the wallet now shows `12 Kč` balance, transaction appears in
    "Activity"
11. Click "Redeem", enter `5`, submit
12. Verify balance is `7 Kč`
13. Click "Manual adjust", type `subtract`, amount `2`, note `Test`, submit
14. Verify balance is `5 Kč`
15. Sign in as `admin@teacha.test` / `password`
16. Visit `/staff/settings`, change `cashback_rate` to `20`, save
17. Log another purchase of `100` → verify the customer now gets `20 Kč`
    back instead of `10`
18. Disable network, reload the wallet page → verify `/offline` renders
19. Open the site on a phone, accept the install prompt → verify it opens
    standalone

## Release-readiness checks (Phase 7)

- [ ] `phpstan-baseline.neon` is **not** introduced
- [ ] All new public routes are rate-limited or auth-gated
- [ ] `public_token` is 32 chars URL-safe (192 bits of entropy)
- [ ] All money columns are `decimal(10,2)` (no `float`)
- [ ] No `env()` outside config files
- [ ] No `phpstan-baseline.neon`, no broad `ignoreErrors` added
- [ ] All new controllers have a `*ControllerTest.php` (CoverageArchitectureTest)
- [ ] All new enums end with `Enum` and expose `public static function values(): array`
- [ ] All new models extend `BaseModel` / `BaseUser`, have `querySelect`,
      `scopeSearch`, and `casts()` where required
- [ ] All new routes use `Resolver::resolveRouteRegistrar()`
- [ ] `make fix` is clean
- [ ] `make check` is clean
- [ ] All services resolve cleanly through `Resolver::resolve(...)` (smoke
      test in tinker / Pest: at least one test per service resolves and
      returns a non-null instance)
- [ ] README covers install, build, seed, PWA, store QR, cashback calc
