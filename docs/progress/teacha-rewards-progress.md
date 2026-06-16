# Teacha Rewards — Progress

> Live tracker. Update as phases move. Source of truth:
> `docs/specs/teacha-rewards-spec.md` and `docs/plans/teacha-rewards-plan.md`.

## State legend

- `[ ]` not started
- `[~]` in progress
- `[x]` done + verified
- `[!]` blocked

## Phase status

- [x] Phase 1 — Domain + database (5/5 tickets done, 0 new PHPStan errors)
- [ ] Phase 2 — Services + unit tests
- [ ] Phase 3 — Public customer flow
- [ ] Phase 4 — Staff surface
- [ ] Phase 5 — PWA
- [ ] Phase 6 — Seed + design polish
- [ ] Phase 7 — Verification + handoff

## Pre-existing build state (blocked / work-in-progress)

The boilerplate was failing PHPStan with 27 pre-existing errors before this
work started. As of this checkpoint:

- 17 of the 27 are fixed:
  - `app/Models/User.php` (fillable `list<string>` → `array<int, string>`, `conversations()` `->orderBy` removed)
  - `app/Http/Controllers/Api/Auth/RegisterController.php` (DB::transaction `@var User` annotation)
  - `app/Http/Controllers/Api/Me/MeUpdateController.php` (`BaseUser` → `App\Models\User` type tightening)
  - `app/Http/Controllers/Web/Auth/RegisterController.php` (DB::transaction `@var User`)
  - `app/Ai/AgentRunService.php` (early-return narrowing instead of `instanceof ? : null`)
  - `app/Ai/ConversationRepository.php` (`@var` annotations + `orderBy` moved to caller)
  - `app/Http/Controllers/Web/Agent/AgentRunStreamController.php` (early `continue` on non-`AgentRunEvent`)
  - `packages/thinkycz/laravel-core/src/Http/Middleware/SetPreferredLanguageMiddleware.php` (`Authenticatable` → `Model` instance check)
  - `packages/thinkycz/laravel-core/src/Traits/ModelTrait.php` (`@param array<mixed> $options` on `save()`)

- 10 remain. They are all in the same pattern: PHPStan cannot infer the
  return type of `Model::query()->first()`, `Model::create()`, or
  `Model::find()` for the `Laravel\Ai\Models\Conversation` and the local
  `App\Models\AgentRun` / `AgentRunEvent` models. The error in each case
  is the same shape (`Instanceof between stdClass|null and X will always
  evaluate to false` or `Call to an undefined static method`). The fix
  is either:
  1. Add `phpstan-extension` stubs for `Laravel\Ai\Models\Conversation`
     that declare `create()`/`find()` as static methods returning
     `static`/`static|null`, plus similar `@phpstan-method` annotations
     on the local `AgentRun` / `AgentRunEvent` models, **or**
  2. Replace the Eloquent chain with `ModelTrait::findByKey`-style
     helpers (e.g. via `assertNullableInstance`).

  These need to be done in a follow-up before the `make check` gate
  can go green.

- 1 additional error: `sseEvent` is reported as unused in
  `AgentRunStreamController`. This is likely a false positive from the
  strict config (the method is yielded from inside a `Generator`).
  Needs investigation.

## Traceability matrix

| Plan ticket | Spec section | Files | Status | Verified by |
|---|---|---|---|---|
| T1.1 add `name` + `role` to `users` | §3.1 | `database/migrations/..._add_name_and_role_to_users_table.php`, `app/Models/User.php`, `database/factories/UserFactory.php` | [x] | PHPStan clean, migrate runs, factory states |
| T1.2 enums | §4 | `app/Enums/{UserRoleEnum,WalletStatusEnum,TransactionTypeEnum,ManualAdjustmentTypeEnum}.php` | [x] | `EnumArchitectureTest` |
| T1.3 reward_wallets | §3.2 | `database/migrations/..._create_reward_wallets_table.php`, `app/Models/RewardWallet.php`, `database/factories/RewardWalletFactory.php` | [x] | `ModelArchitectureTest`, PHPStan clean |
| T1.4 reward_transactions | §3.3 | `database/migrations/..._create_reward_transactions_table.php`, `app/Models/RewardTransaction.php` | [x] | `ModelArchitectureTest`, PHPStan clean |
| T1.5 settings | §3.4 | `database/migrations/..._create_settings_table.php`, `app/Models/Setting.php`, `database/factories/SettingFactory.php` | [x] | `ModelArchitectureTest`, PHPStan clean |
| T2.1 SettingsService | §5.3 | `app/Services/Settings/SettingsService.php`, `app/Providers/RewardServiceProvider.php`, `tests/Unit/Services/Settings/SettingsServiceTest.php` | [ ] | Unit test, PHPStan |
| T2.2 RewardWalletService | §5.1 | `app/Services/Reward/RewardWalletService.php`, `tests/Unit/Services/Reward/RewardWalletServiceTest.php` | [ ] | Unit test |
| T2.3 RewardTransactionService | §5.2 | `app/Services/Reward/RewardTransactionService.php`, `tests/Unit/Services/Reward/RewardTransactionServiceTest.php` | [ ] | Unit test |
| T3.1 EnsureStaffRole middleware | §6.2 | `app/Http/Middleware/EnsureStaffRole.php` | [ ] | Middleware test |
| T3.2 Marketing page | §6.1 / §8.1 | `app/Http/Controllers/Web/Marketing/MarketingIndexController.php`, `resources/js/pages/Marketing/Index.vue` | [ ] | Feature test, type-check |
| T3.3 Wallet create + store | §6.1 / §6.1 | `app/Http/Controllers/Web/Wallet/WalletCreateController.php`, `.../WalletStoreController.php`, `app/Validation/Web/Wallet/StoreWalletValidity.php` | [ ] | `WalletStoreControllerTest` |
| T3.4 StoreWalletValidity | §7 | `app/Validation/Web/Wallet/StoreWalletValidity.php` | [ ] | `ValidationArchitectureTest` |
| T3.5 Wallet show | §6.1 | `.../WalletShowController.php`, `resources/js/pages/Wallet/Show.vue`, `resources/js/components/reward/{WalletCard,RewardsBalance,QRCodeBlock}.vue` | [ ] | `WalletShowControllerTest` |
| T3.6 Wallet activity | §6.1 | `.../WalletActivityController.php`, `resources/js/pages/Wallet/Activity.vue`, `resources/js/components/reward/{TransactionList,TransactionItem}.vue` | [ ] | `WalletActivityControllerTest` |
| T3.7 Public feature tests | §11 | `tests/Feature/App/Http/Controllers/Web/Wallet/*Test.php` | [ ] | `make check` |
| T4.1 Routes | §6.2 | `routes/web.php` (additions only) | [ ] | `RouteArchitectureTest` |
| T4.2 Staff dashboard | §6.2 | `Web/Staff/DashboardController.php`, `pages/Staff/Dashboard.vue` | [ ] | `DashboardControllerTest` |
| T4.3 Staff wallet list / detail | §6.2 | `Web/Staff/Wallets/{WalletIndex,WalletShow}Controller.php`, `pages/Staff/Wallets/{Index,Show}.vue` | [ ] | `WalletIndexControllerTest`, `WalletShowControllerTest` |
| T4.4 Scanner | §6.2 | `Web/Staff/Scan/{ScanIndex,ScanShow}Controller.php`, `pages/Staff/Scan/{Index,Show}.vue` | [ ] | `ScanShowControllerTest` |
| T4.5 Log purchase / redeem / adjust | §6.2 | `Web/Staff/Wallets/{LogPurchase,Redeem,Adjust}Controller.php`, `app/Validation/Web/Staff/{LogPurchase,Redeem,ManualAdjust}Validity.php` | [ ] | Per-controller tests |
| T4.6 Disable / enable | §6.2 | `Web/Staff/Wallets/{Disable,Enable}Controller.php` | [ ] | Per-controller tests |
| T4.7 Transactions list | §6.2 | `Web/Staff/Transactions/TransactionIndexController.php`, `pages/Staff/Transactions/Index.vue` | [ ] | `TransactionIndexControllerTest` |
| T4.8 Settings (admin only) | §6.2 | `Web/Staff/Settings/{SettingsEdit,SettingsUpdate}Controller.php`, `app/Http/Middleware/EnsureAdminRole.php`, `app/Validation/Web/Staff/SettingsValidity.php`, `pages/Staff/Settings/Index.vue` | [ ] | `SettingsControllerTest` |
| T4.9 Staff feature tests | §11 | `tests/Feature/App/Http/Controllers/Web/Staff/**/*Test.php` | [ ] | `make check` |
| T5.1 manifest | §9 | `public/manifest.json`, `public/icons/*`, `tools/build-pwa-icons.cjs` | [ ] | `manifest.json` valid, Lighthouse PWA pass |
| T5.2 service worker | §9 | `public/sw.js` | [ ] | Offline reload test |
| T5.3 SW registration | §9 | `resources/js/app.ts` | [ ] | Type-check, browser test |
| T5.4 Offline route | §9 | `Web/Pwa/OfflineController.php`, `pages/Pwa/Offline.vue`, `public/offline.html` | [ ] | Manual test |
| T5.5 Install banner | §9 | `components/pwa/PwaInstallBanner.vue` | [ ] | Browser test |
| T5.6 Add-to-home-screen guide | §9 | `components/pwa/AddToHomeScreenGuide.vue` | [ ] | Browser test |
| T6.1 TeachaRewardsSeeder | §10 | `database/seeders/TeachaRewardsSeeder.php` | [ ] | `migrate:fresh --seed` |
| T6.2 DatabaseSeeder | §10 | `database/seeders/DatabaseSeeder.php` | [ ] | `make local` |
| T6.3 Design tokens | §8.3 | `resources/css/app.css` (`@theme` block) | [ ] | Visual review |
| T6.4 Frontend polish | §8 | pages + components | [ ] | Visual review |
| T7.1 verification-before-completion | §11–§12 | n/a | [ ] | `make check`, manual run-through |
| T7.2 release-readiness | n/a | n/a | [ ] | `release-readiness` skill output |
| T7.3 README | §12 | `README.md` | [ ] | Section checklist |

## Open questions

_None at session start. New ones should be appended here with the date and the
ticket(s) they block._

## Blockers

_None at session start. New blockers should be appended here with the date
and the ticket(s) they block._

## Resume checklist (next session)

If this work is picked up in a fresh turn, the next agent should:

1. **Finish the pre-existing PHPStan cleanup (10 errors remain).** The
   pattern is the same in all 10: PHPStan cannot infer the static return
   type of `Model::query()->first()`, `Model::create()`, or
   `Model::find()` for the `Laravel\Ai\Models\Conversation` and the local
   `App\Models\AgentRun` / `App\Models\AgentRunEvent` models. Pick one of
   these two fixes:
   - Add a `stubs/Models/Conversation.stub` (and the same for
     `AgentRun`/`AgentRunEvent`) declaring `create()`/`find()`/`first()` as
     template-typed static methods, then include them in `phpstan.neon`'s
     `scanFiles` + `stubFiles`.
   - Add `@phpstan-method static find(int|string $id)` and
     `@phpstan-method static create(array<mixed> $attributes)` annotations
     directly on the three model classes.
   The `sseEvent` "unused" report in `AgentRunStreamController` is
   investigated separately — likely a false positive from the
   bleedingEdge strict rules not following the `Generator` yield.

2. **Phase 2 — services.** The three services in `app/Services/{Reward,Settings}/*`
   and the `RewardServiceProvider` in `app/Providers/`. The plan §Phase 2
   has the exact API surface. Two new composer dependencies are needed:
   `propaganistas/laravel-phone` (Czech phone validation + E.164 cast)
   and `brick/math` (typed decimal math). The wallet-side `getRewardsBalance()`
   currently returns a `string`; the service converts to `BigDecimal` for
   arithmetic and persists the rounded `toScale(2, RoundingMode::HALF_UP)`
   string.

3. **Phase 3+** follows the plan §Phase 3 / 4 / 5 / 6 / 7 in order. Each
   phase has its own acceptance criteria; the plan + verification doc are
   the source of truth.

## Change log

- **2026-06-16** — Initial spec, plan, and progress documents created from
  the `grill-with-docs` closeout. No implementation has started.
- **2026-06-16** — Phase 1 delivered: 4 enums, 3 models, 4 migrations, 2
  factories, User model + factory extended with `name`/`role` + admin/staff
  states. `migrate:fresh` runs cleanly. PHPStan on every new file: 0
  errors. Pre-existing build state: 17/27 PHPStan errors fixed (all in
  code outside the new Teacha Rewards surface). The remaining 10 require
  PHPStan stubs or `@phpstan-method` annotations; deferred to a follow-up
  checkpoint per the user-approved plan. Phases 2–7 not started.
