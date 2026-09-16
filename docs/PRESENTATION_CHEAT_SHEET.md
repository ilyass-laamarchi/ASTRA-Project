# ASTRA Presentation Cheat Sheet

## One-sentence project summary

ASTRA is a Vue 3 + Laravel 13 + MySQL vehicle-rental platform for Tanger that separates Client, Responsable, and Admin work while preventing double booking with transactions and row locks.

## Architecture

```text
Vue -> Axios JSON -> Laravel routes/controllers/services -> Eloquent -> MySQL
Laravel events -> Reverb -> Echo -> Vue refresh
```

## Roles

- Client: personal dashboard, own reservations/payments/profile.
- Responsable (`owner`): daily fleet, clients, reservations, payment view.
- Admin: all operations plus analytics, staff, activations, settings, refunds.
- Public registration always creates Client.

## Critical reservation rule

```text
[start_date, end_date[
10 Aug -> 15 Aug = 5 days

overlap = existing.start < requested.end
          AND existing.end > requested.start
```

`pending` and `confirmed` block. Other statuses release dates.

Concurrency protection:

```text
DB transaction -> lockForUpdate(car) -> overlap recheck -> server price -> save
```

A collision returns HTTP 409.

## Security points

- Sanctum bearer tokens.
- Hashed passwords.
- Backend role middleware and ownership checks.
- Form validation and `$fillable`.
- Client-only public registration.
- Server-calculated duration/amount.
- Signed Stripe webhook controls payment success.
- Secrets stay in `.env`.

## Payments

Implemented: eligibility, Checkout creation, signed webhook, payment table, receipts, staff view, admin refund.

External values needed: `PAYMENT_PUBLIC_KEY`, `PAYMENT_SECRET_KEY`, `PAYMENT_WEBHOOK_SECRET`.

Google needs: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.

## Docker services

- frontend: `localhost:5173`
- api: `localhost:8000/api`
- reverb: `localhost:8080`
- MySQL/DBeaver: `127.0.0.1:3306`, database `astra`
- Laravel reaches MySQL as `database:3306`.

## Commands

```powershell
docker compose up -d
docker compose ps
docker compose logs --tail=100 api
docker compose exec api php artisan route:list
docker compose exec api vendor/bin/phpunit
docker compose exec frontend npm test -- --run
docker compose exec frontend npm run build
docker compose exec frontend npm run test:e2e
docker compose stop
```

Never use `migrate:fresh`, `db:wipe`, or `docker compose down -v` on development data.

## 10–15 minute order

1. Tanger rental problem and ASTRA solution.
2. Architecture and technology stack.
3. Roles and backend authorization.
4. Database relationships.
5. Public catalogue and car details.
6. Client portal and booking.
7. Responsable operations.
8. Admin analytics/management.
9. Half-open dates and overlap formula.
10. Transaction + `lockForUpdate()`.
11. Realtime and Stripe architecture.
12. Security and tests.
13. Conclusion.

## Likely questions

- **Why Laravel?** Clear conventions for routing, validation, auth, Eloquent, transactions, and tests.
- **Why Vue?** Reactive SPA and reusable components.
- **Why MySQL?** Relationships, foreign keys, transactions, indexes, row locks.
- **What is Axios?** The Vue HTTP client for Laravel JSON endpoints.
- **What is Sanctum?** API token authentication.
- **What is Eloquent?** Laravel's model-to-table ORM.
- **Why end date exclusive?** Correct rental duration and boundary handover.
- **How prevent double booking?** Transaction, car row lock, recheck, HTTP 409.
- **Why Reverb?** Fast availability/notification updates; focus/polling is fallback.
- **Why webhook?** Only a signed provider event may confirm payment.
- **Frontend guard or backend middleware?** Both, but backend middleware is security.
- **How is client isolation proved?** Ownership queries plus Laravel and Playwright tests.

## Code walkthrough order

`compose.yaml` -> `routes/api.php` -> `AuthController` -> `User` -> `ReservationController` -> `CarAvailabilityService` -> `Reservation` -> `api.js` -> `auth.js` -> router -> calendar -> realtime -> tests.
