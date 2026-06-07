# Inertia Vue boilerplate implementation plan

## Phases

1. Seed the new project from the reference skeleton and prune sample domain/OpenAPI surfaces.
2. Add Inertia/Vue/TypeScript/Tailwind manifests, Vite config, root Blade view, and middleware.
3. Implement web auth, dashboard, settings routes/controllers, and Vue pages.
4. Keep minimal API-compatible auth routes and user JSON:API resource.
5. Update docs, tests, and verification evidence.

## Defaults

- SSR remains disabled.
- Web forms use redirects and Inertia validation errors.
- API compatibility remains auth-only.
