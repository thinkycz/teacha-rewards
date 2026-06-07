# Inertia Vue boilerplate spec

## Source

User requested a new boilerplate like `laravel-forendors-reference`, but Inertia-first with Vue.

## Requirements

- Keep Laravel 13/PHP 8.3 and `thinkycz/laravel-core` conventions.
- Use the reference database-token cookie auth model.
- Make Inertia/Vue the primary web surface.
- Use the official Laravel Vue starter-kit style: Vue 3, TypeScript, Inertia, Tailwind, shadcn-vue-style structure.
- Omit the reference catalog/order sample domain.
- Keep minimal auth API compatibility.
- Defer SSR.

## Implemented routes

- Web auth: `/login`, `/register`, `/forgot-password`, `/reset-password`, `/logout`
- Web app: `/dashboard`, `/verify-email`, `/settings/profile`, `/settings/password`
- API compatibility: `/api/v1/auth`, `/api/v1/me`, `/api/v1/password`, `/api/v1/email_verification`
