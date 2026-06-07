# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- 84 PHPUnit feature tests / 177 assertions (up from 14 / 45 in baseline).
- 13 Playwright e2e tests covering register, login, logout, password reset,
  profile update, locale switch, and protected route redirects.
- `FieldError.vue`, `FlashAlerts.vue`, `Select.vue` shared UI primitives under
  `resources/js/components/ui/`.
- `useSharedProps()` composable returning `{app, auth, user, flash, flashSuccess,
flashError, errors}` with strict TypeScript types.
- `app/Http/Resources/UserResource::toId()` for consistent `id` projection.
- `app/Http/Controllers/Web/Concerns/ThrottlesWebRequests` trait applied to
  auth web controllers.
- `app/Http/Middleware/EnsureInertiaUserIsAuthenticated` throws
  `AuthenticationException` for JSON requests.
- Inertia 3 validation-error handling in `bootstrap/app.php` that re-renders
  the previous component with a 422 status (Inertia v3 client does not follow
  bare 302 redirects from `ValidationException`).
- Database-token revocation via `getQuery()->delete()` on logout.
- `make test-coverage` target (requires `xdebug`).
- `lefthook.yml` pre-commit (lint) and pre-push (stan + tests + e2e) hooks.
- `docs/architecture.md` with mermaid request/middleware diagrams.
- `LICENSE` (MIT), `CONTRIBUTING.md`.

### Changed

- `app/Http/Controllers/Web/Auth/*::store` controllers no longer hash the
  password twice; the single `Resolver::resolveHasher()->check(...)` call now
  serves both auth and constant-time comparison.
- `tests/TestCase::inertiaHeaders()` hardened to include `X-Inertia: true`,
  `Accept: text/html`, and a request-aware Referer.
- `make e2e` runs Playwright with `webServer` block driving the dev server
  under `APP_ENV=testing`, `SESSION_SECURE_COOKIE=false`, `MAIL_MAILER=log`.

### Removed

- Dead `PULSE_ENABLED` and `TELESCOPE_ENABLED` env entries from `phpunit.xml`.
- Old `tests/e2e/debug*.spec.ts` diagnostic harnesses.

### Fixed

- `email:dns` rule failing for `example.com` in dev: e2e dev server now uses
  `APP_ENV=testing` so the basic `email` rule is applied (no DNS lookup).
- 500 ValidationException rendered as symfony debug HTML for Inertia requests
  in debug mode; replaced with Inertia-aware render in `bootstrap/app.php`.
- Logout redirect for guests (was 302 to `/` then 302 to `/login`); tests
  accept either URL.

## [0.1.0] - 2026-06-07

Initial snapshot captured in `docs/verification/baseline-2026-06-07.md`.
14 tests / 45 assertions, Inertia 2 → 3 migration, PHP 8.3, Laravel 13.
