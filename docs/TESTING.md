# ASTRA Testing and Final Verification

The test strategy separates backend logic, frontend logic, browser behavior, and compilation. It protects the persistent MySQL `astra` database.

## 1. Database safety

Backend PHPUnit uses the values in `backend/phpunit.xml`:

```text
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

`RefreshDatabase` therefore rebuilds only the in-memory test database. It does not touch the Docker MySQL volume.

Run `vendor/bin/phpunit` directly in Docker. It reads `phpunit.xml` before Laravel boots, so the container's development MySQL environment cannot replace the forced SQLite settings.

Never run these against development:

```text
php artisan migrate:fresh
php artisan db:wipe
docker compose down -v
```

Playwright scenarios may call the real local API. Before a full E2E run, take a verified database snapshot and record table counts/current avatar paths. Restore that snapshot afterward, then prove the counts and key real records match the baseline.

## 2. Backend tests

```powershell
docker compose exec api vendor/bin/phpunit
```

Because the API container runs image-contained backend source, rebuild it after
backend changes before live HTTP or browser verification:

```powershell
docker compose up -d --build api reverb
```

Coverage includes:

- client-only public registration and role injection rejection;
- active account login and Google OAuth behavior;
- avatar ownership/persistence path;
- public fleet visibility;
- backend price calculation;
- client reservation/payment isolation;
- `[start, end[` overlap and boundary behavior;
- pending/confirmed blockers;
- transaction conflict rules and HTTP 409;
- status transitions and broadcast events;
- checkout eligibility, webhook/refund architecture;
- notification preferences, settings, contact, and idempotent seeders;
- Admin vs Responsable dashboard payloads.

Formatting check:

```powershell
docker compose exec api ./vendor/bin/pint --test
```

## 3. Frontend unit tests

```powershell
docker compose exec frontend npm test -- --run
```

Vitest covers date-only conversion/duration, media URL generation, reservation payloads, client dashboard derivations, and route metadata/guards.

## 4. Production build

```powershell
docker compose exec frontend npm run build
```

The build proves all Vue templates, imports, and CSS compile. `frontend/dist` is generated output and must not be treated as custom source or committed for this development workflow.

## 5. Playwright

```powershell
docker compose exec frontend npm run test:e2e
```

The full suite covers:

- public homepage/catalogue/details/auth pages;
- current fleet images and responsive pages;
- registration and route security;
- client dashboard isolation;
- reservation route and status workflow;
- concurrent two-client booking safety;
- staff dashboard differences;
- optional real-token avatar/payment verification.

The optional profile/payment spec is skipped unless `ASTRA_REAL_CLIENT_TOKEN` is supplied.

Generated `test-results`, traces, screenshots, videos, and `dist` should be removed after results are recorded.

## 6. Route and source audits

```powershell
docker compose exec api php artisan route:list
rg -n "console\.(log|debug|error)|dd\(|dump\(|var_dump|TODO|FIXME" backend/app backend/routes frontend/src
```

Also scan meaningful source files for a file explanation and custom functions for a concise English comment/docblock.

## 7. Real-browser smoke matrix

Public:

- `/`
- `/cars`
- one `/cars/{id}`
- `/login`
- `/register`

Client:

- `/client/dashboard`
- `/client/reservations`
- `/client/payments`
- `/client/profile`

Responsable:

- `/owner/dashboard`
- `/owner/categories`
- `/owner/cars`
- `/owner/reservations`

Admin:

- `/admin/dashboard`
- `/admin/staff`
- `/admin/clients`
- `/admin/categories`
- `/admin/cars`
- `/admin/reservations`
- `/admin/payments`
- `/admin/settings`

For each role, verify no broken route, no broken image, no visible console error, and no menu/data belonging to another role.

## 8. Database before/after query

```powershell
docker compose exec -T database mysql -uastra -pastra_local_password astra -N -e "SELECT 'users',COUNT(*) FROM users UNION ALL SELECT 'cars',COUNT(*) FROM cars UNION ALL SELECT 'reservations',COUNT(*) FROM reservations UNION ALL SELECT 'payments',COUNT(*) FROM payments;"
```

Compare the same query after cleanup. Legitimate development rows must remain unchanged.
