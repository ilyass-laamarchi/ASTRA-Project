# ASTRA — Premium Vehicle Rental

ASTRA is a full-stack vehicle-rental platform for a business operating in **Tanger, Morocco**. It combines a premium Vue interface with a secure Laravel REST API, persistent MySQL data, and realtime Reverb updates.

The project is organized for an engineering presentation: code comments explain custom files and functions, focused services protect the important business rules, and the `docs/` folder provides complete study material.

## Features

- Public homepage, fleet catalogue, vehicle details, availability, about, and contact.
- Email/password authentication with Sanctum and Google OAuth architecture.
- Public registration restricted to the Client role.
- Personal Client dashboard, reservations, payments, notifications, and profile avatar.
- Responsable operational dashboard and fleet/reservation management.
- Admin analytics, clients, staff, settings, account activation, and refunds.
- Safe half-open rental intervals, server pricing, transactions, row locks, and HTTP 409 conflicts.
- Stripe Checkout/webhook/refund architecture without fake payment success.
- Reverb/Echo events with focus, visibility, and polling refresh fallbacks.

## Stack

| Layer | Technology |
|---|---|
| Frontend | Vue 3, Vite, Vue Router, Pinia, Axios, Tailwind CSS |
| Backend | Laravel 13, PHP 8.3+, Sanctum, Socialite |
| Database | MySQL 8.4 |
| Realtime | Laravel Reverb, Echo, Pusher protocol |
| Payments | Stripe PHP SDK |
| Tests | PHPUnit, Vitest, Playwright |
| Runtime | Docker Compose |

## Architecture

```text
Browser Vue SPA
  -> Axios / JSON
  -> Laravel /api routes
  -> Controllers and focused Services
  -> Eloquent Models
  -> MySQL

Laravel Events -> Reverb -> Echo -> Vue refresh
```

## Roles

| Role | Scope |
|---|---|
| Client | Own dashboard, reservations, payments, profile, notifications |
| Responsable (`owner`) | Daily fleet, categories, clients, reservations, payment view |
| Admin | Responsable features plus analytics, staff, activations, settings, refunds |

Frontend guards improve navigation. Laravel Sanctum, role middleware, and ownership checks enforce security.

## Project structure

```text
ASTRA PROJECT codex/
├── backend/       Laravel application, migrations, seeders, tests
├── frontend/      Vue application, assets, Vitest and Playwright tests
├── docs/          Master, study, API, database, testing documents
├── compose.yaml   MySQL, API, Reverb, frontend services
├── .gitignore
└── README.md
```

## Setup and run

Requirements: Docker Desktop with Docker Compose.

```powershell
# First setup only
Copy-Item backend/.env.example backend/.env
Copy-Item frontend/.env.example frontend/.env
docker compose up --build -d
docker compose exec api php artisan key:generate
docker compose exec api php artisan migrate

# Normal start
docker compose up -d
docker compose ps
```

The Vue source is bind-mounted for Vite HMR. Laravel and Reverb run from the
built API image with four PHP development-server workers, so rebuild those
services after backend source changes:

```powershell
docker compose up -d --build api reverb
```

Uploaded files live in the named `public-storage` volume and therefore survive
container rebuilds and restarts.

Do not run `migrate:fresh`, `db:wipe`, or `docker compose down -v` on the persistent development database.

## Local services

| Service | Host address |
|---|---|
| Vue app | `http://localhost:5173` |
| Laravel API | `http://localhost:8000/api` |
| Reverb | `ws://localhost:8080` |
| MySQL / DBeaver | `127.0.0.1:3306`, database `astra` |

Laravel runs inside Docker and reaches MySQL at `database:3306`. DBeaver runs on Windows and therefore uses `127.0.0.1:3306`.

## Critical reservation rule

ASTRA uses `[start_date, end_date[`. For example, 10 August to 15 August is five days. A blocking overlap exists when:

```text
existing.start_date < requested.end_date
AND existing.end_date > requested.start_date
```

Only `pending` and `confirmed` reservations block dates. Creation locks the car row with `lockForUpdate()`, checks overlap again inside a transaction, calculates price on the backend, and returns 409 if another booking won the race.

## External configuration

Google OAuth requires:

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`

Stripe requires:

- `PAYMENT_PUBLIC_KEY`
- `PAYMENT_SECRET_KEY`
- `PAYMENT_WEBHOOK_SECRET`

Real values belong only in `backend/.env`. The application fails closed when a provider is unavailable.

## Tests

```powershell
docker compose exec api vendor/bin/phpunit
docker compose exec api ./vendor/bin/pint --test
docker compose exec api php artisan route:list
docker compose exec frontend npm test -- --run
docker compose exec frontend npm run build
docker compose exec frontend npm run test:e2e
```

Laravel tests use SQLite `:memory:` through `phpunit.xml`; they do not reset the MySQL `astra` database.

## Documentation

- [Complete master guide](docs/ASTRA_COMPLETE_GUIDE.md)
- [Project file index](docs/PROJECT_FILE_INDEX.md)
- [Backend study guide](docs/BACKEND_STUDY_GUIDE.md)
- [Frontend study guide](docs/FRONTEND_STUDY_GUIDE.md)
- [Database study guide](docs/DATABASE_STUDY_GUIDE.md)
- [Presentation cheat sheet](docs/PRESENTATION_CHEAT_SHEET.md)
- [API reference](docs/API.md)
- [Testing and verification](docs/TESTING.md)

## Stop and restart

```powershell
docker compose stop       # stop services, preserve data
docker compose restart    # restart existing containers
docker compose down       # remove containers/network, preserve named volumes
```
