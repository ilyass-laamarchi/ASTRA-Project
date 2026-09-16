# ASTRA Complete Engineering Guide

This is the master study document for the ASTRA premium vehicle-rental project. It explains what the system does, how its parts communicate, and which engineering choices protect its data.

## 1. Project introduction

ASTRA is a full-stack vehicle-rental application for a business operating in **Tanger, Morocco**. Visitors browse the fleet, clients reserve vehicles, Responsables manage daily operations, and administrators manage the whole platform.

The business objective is to make vehicle rental clear and reliable:

- show only active, rentable vehicles to the public;
- let a client select a vehicle and date interval;
- prevent two clients from reserving the same vehicle for overlapping dates;
- calculate duration and price on the trusted backend;
- let staff confirm, reject, cancel, and complete reservations;
- prepare Stripe payment and Google OAuth integrations without faking them when credentials are absent;
- publish important changes in realtime;
- keep every role inside its authorized data scope.

### Main features

- Premium public homepage, catalogue, vehicle details, about, and contact pages.
- Email/password registration and login with Laravel Sanctum tokens.
- Google OAuth architecture through Laravel Socialite.
- Client dashboard, reservations, payments, notifications, and profile photo.
- Responsable operational dashboard and fleet/reservation management.
- Administrator analytics, account activation, staff, settings, and refunds.
- Reverb WebSocket events with frontend Echo listeners and safe refresh fallbacks.
- Docker services for MySQL, Laravel, Reverb, and Vue.

### Technology stack

| Layer | Technology | Purpose |
|---|---|---|
| Browser UI | Vue 3, Vite, Vue Router | Reactive single-page application |
| Shared state | Pinia | Authenticated user and token state |
| HTTP | Axios | JSON requests to Laravel |
| Styling | Tailwind CSS and component CSS | Responsive premium interface |
| API | Laravel 13, PHP 8.3+ | Validation, authorization, and business rules |
| Authentication | Sanctum and Socialite | API tokens and Google OAuth |
| Data | MySQL 8.4 / Eloquent | Persistent relational data |
| Realtime | Laravel Reverb, Echo, Pusher protocol | Availability and notification updates |
| Payments | Stripe PHP SDK | Checkout, signed webhook, and refund architecture |
| Tests | PHPUnit, Vitest, Playwright | Backend, frontend, and browser verification |
| Runtime | Docker Compose | Repeatable local services and persistent volumes |

In development, Vue is bind-mounted into the frontend container for Vite HMR.
Laravel and Reverb execute the source copied into the API image with four PHP
development-server workers. Rebuild `api` and `reverb` after backend edits.
The `public-storage` named volume keeps user uploads across rebuilds.

## 2. Global architecture

```text
Browser
  |
  v
Vue 3 views and components
  |
  v
Pinia / composables / API helper
  |
  v  Axios HTTP + JSON
Laravel /api routes
  |
  v
Controllers -> focused services
  |
  v
Eloquent models
  |
  v
MySQL 8.4
```

The browser never talks to MySQL. Vue sends HTTP requests to Laravel. Laravel validates the request, verifies the user and role, runs business logic, reads or writes through Eloquent, and returns JSON. Vue stores the result and renders it.

Realtime follows a second path:

```text
Laravel saves a change
  -> Laravel event is broadcast
  -> Reverb sends a WebSocket message
  -> Echo receives it in Vue
  -> the affected view refreshes authoritative API data
```

The WebSocket is an optimization, not the only source of truth. Focus and visibility refreshes keep data correct if the socket is temporarily unavailable.

## 3. Root project map

| Path | Responsibility |
|---|---|
| `backend/` | Laravel API, business rules, schema, seeders, and backend tests |
| `frontend/` | Vue application, public assets, shared state, and frontend tests |
| `docs/` | Current engineering, study, API, database, and testing documents |
| `backups/` | User-owned recovery copies; not part of active execution |
| `compose.yaml` | Defines the four local services and persistent volumes |
| `README.md` | Short project introduction and setup entry point |
| `.gitignore` | Excludes secrets, dependencies, caches, and generated output |

## 4. Backend folder guide

### `backend/app`

- `Http/Controllers/Api`: receives API requests and returns JSON.
- `Http/Requests`: reusable validation for public registration and reservation creation.
- `Http/Middleware`: `EnsureRole` rejects requests from unauthorized roles.
- `Http/Resources`: controls the car and reservation fields exposed to Vue.
- `Models`: Eloquent table representations and relationships.
- `Services`: business logic that genuinely benefits from a focused class.
- `Events`: messages broadcast after availability, status, payment, or notification changes.
- `Contracts`: the small payment gateway interface used to test payment logic without calling Stripe.
- `Providers`: binds the payment interface to the Stripe implementation.
- `Console/Commands`: safe dry-run/repair tooling for historical overlapping reservations.

### Other backend folders

- `config/`: Laravel and integration configuration sourced from `.env`.
- `database/migrations/`: reproducible schema history; never rewrite it destructively.
- `database/factories/`: isolated test fixtures.
- `database/seeders/`: idempotent local demo users, settings, fleet, and history.
- `routes/api.php`: the REST contract grouped by public, client, Responsable, and admin access.
- `routes/channels.php`: private broadcast-channel authorization.
- `tests/Feature`: behavior and security regression tests.
- `public/`: Laravel HTTP entry point and public storage link target.

## 5. Frontend folder guide

### `frontend/src`

- `views/`: route-level screens such as home, auth, catalogue, and workspaces.
- `components/`: reusable visual/business UI, including calendars and role modules.
- `stores/auth.js`: the Pinia authentication store.
- `router/index.js`: routes, lazy loading, and frontend navigation guards.
- `lib/api.js`: the single configured Axios instance.
- `lib/dates.js`: timezone-safe date-only helpers.
- `lib/media.js`: converts persisted Laravel media paths into browser URLs.
- `lib/realtime.js`: creates the Echo client when Reverb is configured.
- `lib/clientDashboard.js`: derives client-only summary data and valid actions.
- `composables/useAvailabilitySync.js`: subscribes to availability events and adds focus/polling refreshes.
- `style.css`: global design tokens and shared styles.

### Other frontend folders

- `public/assets/brand`: centralized light and dark transparent ASTRA logos.
- `public/assets/images`: approved UI and persistent fleet images.
- `tests/e2e`: Playwright flows through the real UI and API.
- adjacent `*.test.js`: Vitest unit tests for helpers and router behavior.

## 6. Important file map

| File | Purpose | Called by | Calls / important concept |
|---|---|---|---|
| `routes/api.php` | REST contract and role groups | Axios | Controllers, Sanctum, role middleware |
| `AuthController.php` | Login, registration, profile, OAuth | Auth store and profile UI | User, Sanctum, Socialite, storage |
| `CarController.php` | Public fleet and availability | Public Vue pages | Car resource, availability service |
| `ReservationController.php` | Client creation and staff transitions | Reservation UIs | Transaction, lock, status service |
| `PaymentController.php` | Checkout, webhook, receipts, refunds | Payment UIs and Stripe | Payment service, ownership checks |
| `ManagementController.php` | Fleet, categories, clients, staff, dashboard | Staff workspace | Models, analytics, uploaded images |
| `CarAvailabilityService.php` | Answers whether intervals overlap | Car and reservation controllers | Half-open intervals and blocking statuses |
| `ReservationStatusService.php` | Validates workflow transitions | Reservation controller | Transactions, events, notifications |
| `PaymentService.php` | Trusted payment lifecycle | Payment controller | Stripe gateway, locks, webhook verification |
| `DashboardAnalyticsService.php` | Role-specific summaries | Management controller | Eager loading and aggregate queries |
| `NotificationService.php` | Creates private notifications | Reservation/payment/contact flows | Preferences and broadcast event |
| `User.php` | Accounts and roles | Auth and management | Hashed password and relationships |
| `Reservation.php` | Server-priced booking record | Reservation/payment services | Status constants and relationships |
| `api.js` | Shared Axios client | All frontend data loaders | Base URL, bearer token, 401 handling |
| `auth.js` | Shared auth state | Router, auth view, workspace | Login/register/restore/logout |
| `router/index.js` | Navigation and role guards | Vue application | Lazy views and metadata checks |
| `AvailabilityCalendar.vue` | Date selection and reservation submission | Vehicle detail | Availability API and realtime composable |
| `WorkspaceView.vue` | Role-aware shell | Protected routes | Admin/client modules and notifications |
| `ClientDashboard.vue` | Personal customer portal | Client workspace | Client-owned reservations and active cars |

The complete per-file map is in `PROJECT_FILE_INDEX.md`.

## 7. Authentication flow

### Login

1. `AuthView.vue` collects email and password.
2. `auth.login()` sends `POST /api/login` through the shared Axios client.
3. `AuthController@login` validates credentials and checks `is_active`.
4. Laravel verifies the hashed password.
5. Sanctum creates an API token.
6. Laravel returns the token and safe user fields.
7. Pinia stores the token/user and local storage preserves the session.
8. Vue Router redirects to `/{role}/dashboard`.

The frontend redirect is convenience. The backend middleware is the real security boundary.

### Public registration

1. The register form submits identity, phone, email, and confirmed password.
2. `RegisterRequest` validates the fields and rejects unknown role fields.
3. `AuthController@register` explicitly sets `role = client`.
4. The User model hashes the password through its cast.
5. Sanctum returns a token for the new client.

**A public request can never create an owner or admin.**

### Google OAuth

Vue opens `/api/auth/google/redirect`. Socialite sends the user to Google and receives the callback at `/api/auth/google/callback`. ASTRA finds or creates the matching account; new Google users are clients. Existing admin/owner roles are preserved. The frontend callback stores the returned token and routes by role.

Required external values:

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`

Without them, the API fails closed with a controlled 503 response.

## 8. Role system and authorization

| Capability | Client | Responsable (`owner`) | Admin |
|---|:---:|:---:|:---:|
| Browse public fleet | Yes | Yes | Yes |
| Create own reservation | Yes | No | No |
| View own payments/profile | Yes | Own profile only | Own profile only |
| Manage categories/cars/images | No | Yes | Yes |
| View clients and staff-wide reservations | No | Yes | Yes |
| Confirm/reject/complete reservations | No | Yes | Yes |
| Create/suspend Responsables | No | No | Yes |
| Suspend client accounts | No | No | Yes |
| Edit system settings/refund | No | No | Yes |
| View business revenue analytics | No | No | Yes |

Security occurs in layers:

- Vue Router prevents confusing navigation.
- `auth:sanctum` requires a valid token.
- `role:*` middleware checks the role on every protected endpoint.
- Controllers compare `user_id` for client-owned reservations and payments.
- Resources limit fields returned to the browser.

Hiding a button in Vue is not authorization because a user can call an API directly.

## 9. Reservation flow

### End-to-end creation

1. The client opens a car and selects start/end dates.
2. Vue checks public availability and shows the server-calculated quote.
3. Vue posts the canonical payload to `POST /api/my-reservations`.
4. `StoreReservationRequest` validates dates, car, and authenticated client role.
5. `ReservationController@store` begins a MySQL transaction.
6. The selected car row is read with `lockForUpdate()`.
7. Availability is checked again **inside** the transaction.
8. Rental days are calculated on the backend.
9. Total = current stored daily price × rental days.
10. A pending reservation is saved.
11. Notifications and availability events are dispatched.
12. Vue receives the JSON and routes to the client workspace.

### Half-open date intervals

ASTRA uses `[start_date, end_date[`:

- start date is included;
- end date is the return boundary and is excluded.

Example: `10 Aug -> 15 Aug` is five rental days: 10, 11, 12, 13, 14. Another rental may start on 15 Aug.

### Overlap formula

```text
existing.start_date < requested.end_date
AND existing.end_date > requested.start_date
```

Both conditions mean the periods share at least one rental day. Exact boundary contact is allowed because one interval has already ended.

Only `pending` and `confirmed` block availability. `rejected`, `cancelled`, and `completed` release it.

### Why `lockForUpdate()` matters

Two requests can both pass a pre-check at nearly the same moment. The database lock makes requests for the same car wait in line. When the second request obtains the lock, it repeats the overlap check and sees the first committed reservation. It receives **HTTP 409 Conflict** instead of creating a duplicate booking.

The browser-provided price is ignored. This prevents a client from changing the amount in developer tools.

## 10. Database

### Core relationship diagram

```text
Category 1 ---- N Car 1 ---- N CarImage
                    |
                    | 1
                    |        N
User 1 -------- N Reservation 1 -------- N Payment
  |
  +---------- N AstraNotification

ApplicationSetting: independent key/value configuration
ContactInquiry: public contact submissions
```

### Important tables

| Table | Important fields | Purpose |
|---|---|---|
| `users` | names, email, password, role, active, avatar, preferences | Clients and staff |
| `categories` | name, description, active | Fleet grouping |
| `cars` | category, registration, specs, price, status, active | Rentable inventory |
| `car_images` | car, path, primary, order | Ordered public vehicle images |
| `reservations` | reference, user, car, dates, days, prices, status | Booking history |
| `payments` | reservation, user, provider IDs, amount, status, timestamps | Verified payment lifecycle |
| `astra_notifications` | user, type, title, message, data, read time | Private user notifications |
| `application_settings` | key, value | Agency settings |
| `contact_inquiries` | identity, message, workflow status | Contact submissions |

Laravel also uses migrations, tokens, password reset, cache, sessions, queue, and failed-job tables.

Foreign keys prevent orphan data. Categories/cars/users referenced by history use restricted deletes; car images and notifications use cascade deletion where the child has no independent historical value.

See `DATABASE_STUDY_GUIDE.md` for the actual field-level schema.

## 11. Car and image management

Responsable and Admin workspaces call role-protected endpoints. Staff create/edit car specifications, assign a category, set operational status, and upload images. Laravel validates files and stores their paths in `car_images`. The primary flag chooses the catalogue image; `sort_order` controls gallery order.

Public catalogue queries return only active cars whose operational status is `available`. Eloquent eager-loads category and ordered images to avoid an N+1 query.

## 12. Payment architecture

Implemented application flow:

```text
confirmed + unpaid client reservation
  -> POST checkout request
  -> Laravel checks ownership and eligibility
  -> backend creates amount from reservation record
  -> Stripe Checkout session
  -> Stripe redirects client
  -> signed Stripe webhook reaches Laravel
  -> Laravel verifies signature and locks payment
  -> payments row becomes paid/failed/refunded
  -> payment event and UI refresh
```

ASTRA never stores raw card data and never marks a payment paid from a frontend redirect alone. The signed webhook is authoritative.

Implemented code includes checkout, configuration status, webhook parsing, personal payment history, receipt, staff view, and admin refund. External configuration still requires:

- `PAYMENT_PUBLIC_KEY`
- `PAYMENT_SECRET_KEY`
- `PAYMENT_WEBHOOK_SECRET`

If credentials are absent, the UI reports that payment is unavailable; it does not create fake success.

## 13. Profile photo flow

1. Vue sends multipart form data to `POST /api/profile/avatar`.
2. Laravel validates MIME type and size.
3. The file is stored on the public disk under the authenticated user's path.
4. `users.avatar_path` stores the relative path.
5. The API returns the refreshed user.
6. Pinia updates the shared user immediately.
7. `publicMediaUrl()` converts the path to `http://localhost:8000/storage/...`.
8. Profile and top avatar render the same URL; initials remain the fallback.

Docker mounts `astra_public_storage` at `storage/app/public`, so container recreation does not delete uploads. Laravel's `public/storage` link exposes the volume through the API server.

## 14. Realtime communication

Laravel broadcasts:

- `CarAvailabilityChanged` after blocking dates change;
- `ReservationStatusChanged` after staff/client transitions;
- `PaymentStatusChanged` after verified payment changes;
- `NotificationCreated` for private user notifications.

Reverb is the WebSocket server. Echo is the frontend client. The availability composable subscribes only to the relevant car channel, refreshes authoritative data on an event, and also refreshes on focus/visibility plus a slow polling fallback. If WebSockets fail, the application remains usable; updates may arrive on the next fallback refresh.

## 15. Dashboards

- **Client dashboard:** customer portal only. It uses the authenticated client's reservations/payments and public active cars. It never receives business analytics or another client's data.
- **Responsable dashboard:** operational work: current availability, pending requests, returns, planning, and alerts.
- **Admin dashboard:** strategic view: paid revenue, client totals, fleet/category distribution, vehicle performance, and recent activity.

`DashboardAnalyticsService` returns different payloads by role. Backend role middleware prevents a client from requesting staff analytics.

## 16. Frontend state and HTTP

Pinia is a shared reactive object. ASTRA uses one auth store so header, router, profile, and workspace see the same user and token. Updating the user after an avatar or profile change immediately updates every consumer.

Axios is configured once in `lib/api.js`:

```text
Vue component
  -> api.post('/my-reservations', payload)
  -> HTTP POST http://localhost:8000/api/my-reservations
  -> Authorization: Bearer <Sanctum token>
  -> Laravel JSON response
  -> component/store state
  -> rendered UI
```

Important status codes:

- `200` successful read/update;
- `201` created record;
- `401` missing/invalid authentication;
- `403` authenticated but forbidden;
- `404` record outside visible scope or missing;
- `409` business conflict such as overlapping booking;
- `422` invalid input or transition;
- `503` required external provider is not configured.

## 17. Security summary

- Passwords use Laravel's hashed model cast; plaintext is never stored.
- Sanctum tokens authenticate API requests.
- Role middleware and ownership checks enforce isolation on the backend.
- Public registration fixes the role to client.
- Form Requests and controller validation reject malformed input.
- `$fillable` limits mass assignment; sensitive fields are hidden.
- Transactions and row locks prevent double booking and payment races.
- Payment amount and success are backend/provider controlled.
- `.env` holds secrets and is ignored; `.env.example` contains names only.
- CORS allows configured frontend origins, not arbitrary trust.
- API resources avoid exposing internal notes to clients unless intentionally allowed.

## 18. Docker for a student

- An **image** is a packaged runtime blueprint.
- A **container** is a running instance of an image.
- A **volume** stores data independently of a container lifecycle.
- A **network** lets containers call each other by service name.
- A **port mapping** exposes a container service to Windows.

| Service | Container role | Host access | Internal access |
|---|---|---|---|
| `frontend` | Vite Vue server | `localhost:5173` | calls API through configured URL |
| `api` | Laravel HTTP API | `localhost:8000` | `database:3306`, `reverb:8080` |
| `reverb` | WebSocket event server | `localhost:8080` | shares Laravel code/config |
| `database` | MySQL 8.4 | `127.0.0.1:3306` | `database:3306` |

Laravel uses `database` because Docker DNS knows the service name. DBeaver runs on Windows, outside the Docker network, so it uses the published host address `127.0.0.1`.

Named volumes preserve MySQL data, public avatar uploads, and frontend dependencies.

## 19. Environment files

`.env` gives configuration to one machine without putting secrets in source control. `.env.example` documents required keys with safe placeholders.

- APP: application name, URL, key, environment, debug, timezone.
- DB: host, port, database `astra`, username, password.
- REVERB: app ID/key/secret, host, port, scheme.
- GOOGLE: OAuth client ID, secret, callback.
- PAYMENT: Stripe public key, secret key, webhook secret.

Never put real secrets in Vue or commit them.

## 20. Starting, stopping, and checking ASTRA

From the active project root:

```powershell
# First setup only
Copy-Item backend/.env.example backend/.env
Copy-Item frontend/.env.example frontend/.env
docker compose up --build -d
docker compose exec api php artisan key:generate
docker compose exec api php artisan migrate

# Normal start
docker compose up -d

# Status and logs
docker compose ps
docker compose logs --tail=100 api
docker compose logs --tail=100 reverb

# Stop containers but preserve volumes/data
docker compose stop

# Restart
docker compose restart

# Remove containers but preserve named volumes
docker compose down
```

Never use `migrate:fresh`, `db:wipe`, or `docker compose down -v` on the development database.

URLs:

- App: `http://localhost:5173`
- API: `http://localhost:8000/api`
- Reverb: `ws://localhost:8080`
- DBeaver: `127.0.0.1:3306`, database `astra`

## 21. Debugging checklist

### Frontend problem

1. Open browser DevTools Console and Network.
2. Check the failing URL, status, request payload, and JSON response.
3. Check `docker compose logs --tail=100 frontend`.
4. Confirm `VITE_API_BASE_URL` points to the host API.

### API problem

1. Run `docker compose logs --tail=100 api`.
2. Check routes with `docker compose exec api php artisan route:list`.
3. Read `backend/storage/logs/laravel.log` without exposing secrets.
4. Verify the token, role, and validation response.

### Database problem

1. Run `docker compose ps` and verify database health.
2. Use DBeaver at `127.0.0.1:3306`.
3. Use `php artisan migrate:status`.
4. Inspect exact rows; never reset the database to debug one record.

### Realtime problem

1. Inspect Reverb logs and port 8080.
2. Check browser WebSocket connections.
3. Confirm Reverb frontend/backend keys match.
4. Verify focus refresh still retrieves fresh API data.

## 22. Testing

- **Laravel feature tests:** boot the application with an isolated SQLite `:memory:` database configured in `phpunit.xml`. They cover auth, authorization, availability, payment rules, notifications, settings, and dashboards.
- **Vitest:** tests pure frontend helpers and router decisions quickly in jsdom.
- **Playwright:** runs browser/API flows for public pages, roles, booking concurrency, images, and responsive layouts.
- **Production build:** confirms Vue templates and imports compile for deployment.

Development MySQL data is not a test fixture. Browser tests that intentionally touch it must record identifiers and remove only their own generated records afterward.

## 23. Questions my teacher may ask

1. **Why Laravel?** It provides mature routing, validation, authentication, Eloquent, transactions, and testing with clear conventions.
2. **Why Vue?** It creates a reactive SPA with reusable components and understandable state flow.
3. **Why MySQL?** Rental data is relational and needs transactions, foreign keys, indexes, and row locking.
4. **What is REST?** A convention where HTTP methods and URLs represent operations on resources.
5. **What is Axios?** The frontend HTTP client used to send JSON and tokens to Laravel.
6. **What is Sanctum?** Laravel's token authentication system for the SPA API.
7. **What is Eloquent?** Laravel's ORM mapping PHP models and relationships to database tables.
8. **What is a migration?** Version-controlled PHP code that creates or changes schema.
9. **What is Docker?** A repeatable way to run each required service in an isolated container.
10. **Why use a volume?** It keeps MySQL and uploaded files after containers are recreated.
11. **What is Reverb?** Laravel's WebSocket server for pushing events to browsers.
12. **What is Echo?** The JavaScript client that subscribes to Laravel broadcast channels.
13. **Why realtime?** It quickly refreshes availability when another user changes a reservation.
14. **What if WebSocket fails?** Focus, visibility, and polling fallbacks refresh from the API.
15. **Why `lockForUpdate()`?** It serializes simultaneous booking decisions for the same car.
16. **How is double booking prevented?** Transaction, car row lock, overlap recheck, and HTTP 409.
17. **Why is the end date exclusive?** It matches return-day rental logic and allows boundary-to-boundary bookings.
18. **What is HTTP 409?** The request is valid but conflicts with current business state.
19. **Admin vs Responsable?** Responsable manages daily operations; Admin additionally manages staff, account activation, settings, refunds, and analytics.
20. **How are frontend and backend connected?** Axios calls Laravel `/api` routes and receives JSON.
21. **Where are passwords stored?** Only as secure hashes in `users.password`.
22. **How are images stored?** Files live in public assets or Laravel public storage; database rows store paths and ordering.
23. **How does Stripe work?** Laravel creates checkout; Stripe collects card data; a signed webhook confirms the result.
24. **Why use `.env`?** Configuration and secrets differ per machine and must not be committed.
25. **Why validate on the backend?** A browser can be bypassed or manipulated.
26. **What is mass assignment?** Filling model fields from arrays; `$fillable` limits permitted fields.
27. **Why use API Resources?** They provide a stable, safe JSON shape.
28. **What prevents client data leakage?** Role middleware, ownership queries, controller checks, and tests.
29. **What is Pinia?** A shared reactive store for authentication state.
30. **Why services?** They isolate important reusable business rules without adding unnecessary layers.
31. **Why not place all logic in controllers?** Controllers should coordinate HTTP; services make critical rules testable and readable.
32. **What is an N+1 query?** Repeating one query per row; eager loading prevents it.
33. **How is price protected?** The server reads the stored daily price and calculates the duration.
34. **Why are payment webhooks signed?** To prove the event came from Stripe and was not forged by a client.
35. **How do tests protect development data?** Backend tests use an isolated in-memory database; E2E fixtures are identified and cleaned.

## 24. How to present ASTRA in 10–15 minutes

1. **Problem:** rental businesses need reliable fleet/date coordination.
2. **Solution:** introduce ASTRA and its Tanger business context.
3. **Architecture:** show Vue -> Laravel -> MySQL and Reverb.
4. **Roles:** compare Client, Responsable, Admin.
5. **Database:** show users, cars, reservations, payments, and relationships.
6. **Public website:** catalogue and vehicle availability.
7. **Client portal:** personal dashboard, booking, profile, payments.
8. **Responsable:** daily operational tasks and reservation decisions.
9. **Admin:** analytics, staff, settings, and refunds.
10. **Reservation safety:** explain half-open intervals and overlap formula.
11. **Concurrency:** explain transaction and `lockForUpdate()`.
12. **Security:** Sanctum, roles, ownership, validation, server pricing.
13. **Realtime/payment:** event refresh and verified Stripe webhook.
14. **Tests:** PHPUnit, Vitest, Playwright, build.
15. **Conclusion:** a clear, secure, presentation-ready full-stack project.

## 25. Suggested code walkthrough

1. `compose.yaml` — establishes the four running systems.
2. `backend/routes/api.php` — shows the public and role-protected contract.
3. `AuthController.php` and `RegisterRequest.php` — explains authentication and client-only registration.
4. `User.php` — shows roles, hashing, and relationships.
5. `ReservationController.php` — shows the HTTP entry point for booking.
6. `CarAvailabilityService.php` — shows the overlap rule clearly.
7. `ReservationStatusService.php` — shows controlled transitions.
8. `Reservation.php` and migration — connects code to schema.
9. `frontend/src/lib/api.js` — shows how Vue calls Laravel.
10. `frontend/src/stores/auth.js` — shows shared authenticated state.
11. `frontend/src/router/index.js` — shows navigation guards.
12. `AvailabilityCalendar.vue` — shows UI-to-API booking data.
13. `useAvailabilitySync.js` and events — shows realtime refresh.
14. Tests — prove the most important rules.

This order follows a request from infrastructure to route, backend logic, data, frontend communication, UI, realtime, and proof. It is easier to explain than jumping between unrelated files.

## 26. Clean source tree

```text
ASTRA PROJECT codex/
├── backend/
│   ├── app/                 controllers, models, services, events, requests
│   ├── bootstrap/           Laravel application bootstrap
│   ├── config/              framework and integration configuration
│   ├── database/            migrations, factories, idempotent seeders
│   ├── public/              HTTP entry point and public storage target
│   ├── routes/              API, broadcast, console, and health routes
│   └── tests/               isolated PHPUnit feature tests
├── frontend/
│   ├── public/assets/       centralized brand and approved image assets
│   ├── src/                 Vue views, components, store, router, helpers
│   └── tests/e2e/           Playwright browser and concurrency flows
├── docs/                    current master and study documentation
├── compose.yaml             MySQL, API, Reverb, and Vite services
└── README.md                concise setup and project entry point
```

Dependencies, generated caches, `dist`, and test artifacts are deliberately
excluded from this meaningful tree.

## 27. Final verified baseline (24 August 2026)

- Reviewed custom source: 109 files.
- Laravel: 47 tests, 182 assertions, all passing.
- Pint: 75 files, all passing.
- Vitest: 5 files, 10 tests, all passing.
- Playwright: all 12 scenarios verified; the default suite passed 9 and the
  3 real-account token-dependent scenarios passed in controlled follow-up runs.
- Vue production build: 1,847 modules transformed successfully.
- Routes: 99 registered; all seven migrations report `Ran`.
- HTTP: public Vue routes, `/api/cars`, and Laravel `/up` return 200.
- Restored development data: 34 users, 4 categories, 20 cars, 20 car
  images, 71 reservations, 0 payments, 101 notifications, 7 settings, and
  0 contact inquiries.
- Business location: Tanger, Morocco. Technical timezone: Africa/Casablanca.

Google and Stripe integrations remain intentionally fail-closed until their
credential environment variables are configured. No fake OAuth or payment
success path is enabled.

## 28. Final engineering principles

ASTRA deliberately keeps a small number of clear layers:

```text
Vue view/component -> Axios -> route -> controller -> focused service -> model -> MySQL
```

Simple CRUD stays in the management controller. Complex availability, workflow, payments, notifications, and analytics stay in focused services. Large approved visual pages remain together where splitting them would increase risk without improving business clarity. Correctness and explainability take priority over clever abstraction.
