# Inertia Vue boilerplate verification

## Commands

- `composer install`
- `npm install`
- `php artisan test`
- `npm run type-check`
- `npm run build`
- `php -d zend.assertions=1 ./vendor/bin/phpstan analyse`
- `make fix`
- `make check`

## Result

`make check` passed after implementation.

Evidence from the final run:

- PHPStan: no errors.
- Prettier: all matched files use Prettier code style.
- Pint: passed.
- Composer audit: no security advisories.
- npm audit: no vulnerabilities.
- Frontend type-check: passed.
- Frontend production build: passed.
- Tests: 14 passed, 45 assertions.
