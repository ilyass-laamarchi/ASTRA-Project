# ASTRA Backend Study Guide

This guide explains the Laravel side of ASTRA in beginner-friendly engineering terms.

## 1. What the backend does

The backend is the trusted part of the system. It accepts HTTP requests, validates them, authenticates users, enforces roles and ownership, runs business rules, reads/writes MySQL, and returns JSON.

```text
API route -> middleware -> controller -> focused service -> model -> MySQL
```

Not every request needs every layer. Simple profile or CRUD work can stay in a controller. Availability, workflow, payment, notifications, and analytics use services because the logic is shared or important enough to test separately.

## 2. Laravel request lifecycle

Example: a client creates a reservation.

1. `POST /api/my-reservations` matches `routes/api.php`.
2. `auth:sanctum` finds the user from the bearer token.
3. `role:client` runs `EnsureRole`.
4. Laravel injects `StoreReservationRequest`.
5. The Form Request checks role, dates, and car.
6. `ReservationController@store` starts the booking transaction.
7. `CarAvailabilityService` applies overlap rules.
8. `Reservation` writes to MySQL through Eloquent.
9. Events/notifications are emitted.
10. `ReservationResource` returns controlled JSON.

## 3. Routes

`backend/routes/api.php` is the best backend starting point.

- Public routes: login, register, password recovery, Google OAuth, active cars/categories, availability, contact, Stripe webhook.
- Authenticated shared routes: current user, profile/avatar/password, notifications/preferences, logout.
- Client routes: own reservations, checkout, own payments and receipt.
- Owner routes: dashboard, categories, cars/images, clients, reservations, payment view.
- Admin routes: all Owner features plus staff, activations, settings, and refunds.

The project keeps `/api/...`; it does not add an unnecessary `/api/v1` layer.

## 4. Controllers

### `AuthController`

- Uses `RegisterRequest` for public client registration.
- Forces `User::ROLE_CLIENT`; the browser cannot choose the role.
- Checks active status during login.
- Creates/revokes Sanctum tokens.
- Updates only the authenticated profile.
- Stores avatar files on the public disk and returns the refreshed user.
- Uses Socialite for Google redirects/callbacks.

### `CarController`

Returns active, operationally available cars and categories. When dates are present, it filters out cars with blocking overlaps. Resources control the public JSON.

### `ReservationController`

Owns the reservation HTTP endpoints. Client queries always include the authenticated `user_id`. Staff queries are protected by role middleware. Creation contains the transaction/lock because it is the critical write boundary; status changes delegate to `ReservationStatusService`.

### `PaymentController`

Checks reservation/payment ownership before delegating to `PaymentService`. The webhook is public because Stripe cannot send a Sanctum token, but the payload must pass Stripe signature verification.

### `ManagementController`

Contains understandable staff CRUD for categories, cars, images, clients, and staff. It calls `DashboardAnalyticsService` for non-trivial analytics instead of embedding large query logic.

### Other controllers

- `NotificationController`: private list/read/preferences.
- `SettingsController`: Admin-only agency settings and provider status.
- `ContactController`: validates/stores contact messages and alerts staff.

## 5. Form Requests and validation

Form Requests are used where rules are central and reused by a substantial action:

- `RegisterRequest`: identity, unique email, phone, password confirmation; unknown role injection is rejected.
- `StoreReservationRequest`: active authenticated client, valid car, start before end, correct date format.

Small one-off updates use clear controller validation to avoid creating a file for every tiny operation.

Validation returns HTTP 422 with field errors. Business conflicts such as a date collision return 409.

## 6. Middleware and authorization

`EnsureRole` receives allowed role names from the route. It returns 403 if the authenticated user's role is not in the list.

Authorization is also record-specific:

- a client reservation query includes `where user_id = authenticated id`;
- a payment/receipt query does the same;
- profile and avatar actions use only `request->user()`;
- admin-only routes have `role:admin`.

This is why changing a frontend URL or button cannot expose another user's records.

## 7. Eloquent models

### Fillable and hidden

`$fillable` lists fields that may be assigned from an array. `$hidden` removes passwords and payment metadata from ordinary JSON. Validation and explicit controller data still remain necessary.

### Casts

Casts convert stored values into safe PHP types:

- user password uses `hashed`;
- booleans become real booleans;
- dates become Carbon date objects;
- JSON preferences/metadata become arrays;
- money is represented consistently as decimal strings.

### Relationships

Eloquent methods express foreign keys:

- User has many reservations/payments/notifications.
- Category has many cars.
- Car belongs to category and has many images/reservations.
- Reservation belongs to user/car and has many payments.
- Payment belongs to reservation/user.

Eager loading (`with`) retrieves related rows efficiently and prevents N+1 queries.

## 8. Availability service

`CarAvailabilityService` has one clear purpose: answer whether a car is free and return blocked periods.

Blocking status constants come from `Reservation`:

```text
pending, confirmed -> block
rejected, cancelled, completed -> do not block
```

Overlap:

```text
existing.start_date < requested.end_date
AND existing.end_date > requested.start_date
```

This supports `[start, end[` and permits one reservation to start exactly when the previous one ends.

## 9. Safe reservation transaction

The important structure is:

```php
DB::transaction(function () {
    // Lock the car row.
    // Recheck blocking overlap inside the transaction.
    // Calculate duration and price on the backend.
    // Insert one pending reservation.
});
```

`lockForUpdate()` is a pessimistic database lock. Other transactions requesting that car wait. The second transaction sees the first result before deciding. This protects correctness even when two browsers click at the same time.

## 10. Reservation status service

The status service centralizes allowed transitions:

- pending -> confirmed or rejected;
- pending/confirmed -> cancelled when permitted;
- confirmed -> completed.

It locks the reservation, checks its current state, applies the transition, and dispatches availability/status/notification events. Invalid transitions return 422 or a business conflict instead of silently changing history.

## 11. Payment service and gateway

`PaymentService` owns application rules. `PaymentGatewayInterface` describes the three provider operations. `StripePaymentGateway` is the actual adapter.

This small boundary is useful, not enterprise ceremony: backend tests can replace Stripe with a deterministic fake while testing ownership, amount, status, and locking.

Rules:

- only the reservation owner can pay;
- reservation must be confirmed;
- paid/refunded state cannot be chosen by Vue;
- amount comes from `reservations.total_amount`;
- webhook signature is mandatory;
- payment row is locked before state change;
- only Admin may request a refund.

## 12. Notifications and realtime events

`NotificationService` respects user preferences, stores the notification, then broadcasts `NotificationCreated` on a private channel.

Domain events provide small, safe payloads. Channel authorization in `routes/channels.php` ensures a user can subscribe only to their own private channel. Public car availability channels do not include private client data.

## 13. Dashboard analytics

Admin and Owner receive different payloads:

- Admin: paid revenue and strategic business metrics.
- Owner: fleet operations, planning, alerts, and current occupancy.

The service uses aggregate queries and eager-loaded period records. It does not query once per car. Occupancy follows the same half-open date boundary, so a reservation ending today is no longer active today.

## 14. Database migrations and seeders

Migrations are schema history. Never edit old migrations to change a populated environment and never use `migrate:fresh` on development data. Add a new migration for future changes.

Seeders use update/create patterns so the demo fleet and settings are idempotent. Re-running them should not duplicate the 20-car fleet or create blocking overlaps.

Factories are for isolated tests, not the real development database.

## 15. Important configuration

- `config/app.php`: timezone and locale.
- `config/database.php`: Laravel container uses host `database`.
- `config/filesystems.php`: public storage URL.
- `config/cors.php`: browser origin access.
- `config/services.php`: Google and Stripe variables.
- `config/broadcasting.php` and `config/reverb.php`: events/WebSocket.

The `.env` file supplies actual values. Never expose it in API responses or commits.

## 16. Backend testing

`phpunit.xml` uses SQLite `:memory:` for the test process, so `RefreshDatabase` cannot erase the `astra` MySQL volume.

Test groups:

- `AstraApiTest`: auth, role injection, ownership, avatar, price, OAuth.
- `AvailabilitySystemTest`: date validation, blockers, boundaries, 409, events, payment eligibility, seeder safety.
- `DashboardAnalyticsTest`: correct role payloads and paid revenue.
- `FunctionalCompletionTest`: profile, reset, notification, settings, contact, seeders.

Run:

```powershell
docker compose exec api vendor/bin/phpunit
docker compose exec api php artisan route:list
docker compose exec api ./vendor/bin/pint --test
```

## 17. Backend explanation summary

When presenting, remember this sentence:

> Laravel is ASTRA's trust boundary: routes and middleware identify who may call an action, controllers coordinate HTTP, focused services protect important business rules, Eloquent persists relational data, and tests prove security and concurrency behavior.
