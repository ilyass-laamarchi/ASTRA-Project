# ASTRA Database Study Guide

ASTRA uses MySQL 8.4 with the InnoDB engine in development. Laravel migrations define the schema and Eloquent models access it. The database name is `astra`.

## 1. Why a relational database

Rental data has strong relationships: a reservation must reference one existing client and one existing car; a car belongs to a category; a payment belongs to a reservation. Foreign keys and transactions make these rules reliable.

MySQL is also important for `SELECT ... FOR UPDATE`, used by Laravel's `lockForUpdate()` to prevent concurrent double booking.

## 2. Entity relationship diagram

```text
users (1) --------------------< reservations >-------------------- (1) cars
  |                                  |                               |
  |                                  |                               +----< car_images
  |                                  +----< payments                 |
  +----< payments                                                   (1)
  +----< astra_notifications                                        |
                                                              categories

application_settings     contact_inquiries
(independent key/value)  (independent public messages)
```

Cardinality:

- one User has many Reservations;
- one Car has many Reservations;
- one Category has many Cars;
- one Car has many CarImages;
- one Reservation can have multiple payment attempts;
- one User owns many Payments and Notifications.

## 3. Core table dictionary

### `users`

| Field | Type / rule | Meaning |
|---|---|---|
| `id` | bigint primary key | Account identifier |
| `first_name`, `last_name` | string | Display identity |
| `email` | unique string | Login/OAuth identity |
| `phone` | nullable string(30) | Contact number |
| `password` | string | Secure password hash |
| `role` | enum client/owner/admin | Authorization role |
| `is_active` | indexed boolean | Suspended accounts cannot log in |
| `avatar_path` | nullable string | Public storage URL/path |
| `notification_preferences` | nullable JSON | Reservation/payment/system choices |
| `remember_token` | nullable token | Laravel compatibility |
| timestamps | created/updated | Audit dates |

Public registration always writes `client` regardless of browser input.

### `categories`

| Field | Rule | Meaning |
|---|---|---|
| `id` | primary key | Category ID |
| `name` | unique | Public label |
| `description` | nullable text | Explanation |
| `is_active` | indexed boolean | Public visibility |

Cars restrict category deletion to protect fleet history.

### `cars`

| Field | Rule | Meaning |
|---|---|---|
| `category_id` | foreign key -> categories | Fleet grouping |
| `registration_number` | unique | Physical vehicle identity |
| `brand`, `model` | indexed strings | Commercial identity |
| `year`, `color`, `seats`, `doors` | validated attributes | Specifications |
| `fuel_type` | gasoline/diesel/hybrid/electric | Engine type |
| `transmission` | manual/automatic | Gearbox |
| `daily_price` | decimal(10,2), indexed | Backend price per rental day |
| `mileage` | unsigned integer | Odometer |
| `description` | nullable text | Public content |
| `operational_status` | available/maintenance/unavailable | Physical availability |
| `is_active` | indexed boolean | Catalogue lifecycle |

A car must be active and operationally available before it can be publicly booked.

### `car_images`

| Field | Rule | Meaning |
|---|---|---|
| `car_id` | foreign key -> cars, cascade delete | Owner car |
| `path` | string | Browser-accessible asset/storage path |
| `alt_text` | nullable string | Accessible description |
| `is_primary` | indexed boolean | Catalogue image flag |
| `sort_order` | unsigned integer | Gallery order |

The composite index `(car_id, sort_order)` supports ordered gallery loading.

### `reservations`

| Field | Rule | Meaning |
|---|---|---|
| `reservation_number` | unique | Public reference |
| `user_id` | FK -> users, restrict delete | Client owner |
| `car_id` | FK -> cars, restrict delete | Reserved vehicle |
| `start_date` | indexed date | Included pickup date |
| `end_date` | indexed date | Excluded return boundary |
| `rental_days` | unsigned integer | Server-calculated duration |
| `daily_price` | decimal(10,2) | Price snapshot at booking |
| `total_amount` | decimal(12,2) | Server-calculated total |
| `status` | pending/confirmed/rejected/cancelled/completed | Workflow state |
| `client_message` | nullable text | Client note |
| `internal_note` | nullable text | Staff reason/note |

The composite index `(car_id, status, start_date, end_date)` supports overlap checks.

The daily price is copied into the reservation so later fleet price changes do not rewrite historical amounts.

### `payments`

| Field | Rule | Meaning |
|---|---|---|
| `reservation_id` | FK -> reservations, restrict | Booking being paid |
| `user_id` | FK -> users, restrict | Client owner |
| `payment_reference` | unique | ASTRA reference |
| `provider` | default stripe | External provider |
| `provider_session_id` | nullable unique | Checkout session |
| `provider_payment_id` | nullable unique | Provider transaction |
| `amount` | decimal(12,2) | Backend-controlled reservation amount |
| `currency` | char(3), default MAD | Currency |
| `status` | pending/processing/paid/failed/cancelled/refunded | Lifecycle |
| `payment_method` | nullable | Provider-reported method |
| `failure_reason` | nullable text | Safe provider failure explanation |
| `metadata` | nullable JSON, model-hidden | Provider event metadata |
| `paid_at`, `refunded_at` | nullable timestamps | Verified lifecycle times |

Raw card numbers are never stored.

### `astra_notifications`

| Field | Meaning |
|---|---|
| `user_id` | Private recipient; cascades on user deletion |
| `type` | reservation/payment/system/contact category |
| `title`, `message` | User-facing content |
| `data` | Optional safe JSON context |
| `read_at` | Null means unread |

Indexes on user/date and read time support the header notification list.

### `application_settings`

`key` is the primary key and `value` is nullable text. It stores agency name, email, phone, address, currency, timezone, and booking notice values without creating a wide table for a small configurable set.

### `contact_inquiries`

Stores `name`, `email`, optional `phone`, `message`, and indexed workflow `status` (`new` initially).

## 4. Laravel/framework tables

| Table | Purpose |
|---|---|
| `personal_access_tokens` | Sanctum API tokens |
| `password_reset_tokens` | Short-lived reset token records |
| `sessions` | Database-backed Laravel sessions |
| `cache`, `cache_locks` | Framework cache and mutual exclusion |
| `jobs`, `job_batches`, `failed_jobs` | Queue infrastructure |
| `migrations` | Records which schema migrations ran |

## 5. Foreign-key behavior

- `cars.category_id`: restrict; do not orphan vehicles.
- `car_images.car_id`: cascade; image metadata has no meaning without the car.
- `reservations.user_id` and `car_id`: restrict; preserve rental history.
- `payments.reservation_id` and `user_id`: restrict; preserve financial history.
- `astra_notifications.user_id`: cascade; private transient messages leave with the account.

This is why ASTRA normally deactivates accounts/cars/categories instead of deleting historical parents.

## 6. Reservation date mathematics

The database stores date-only values. A rental uses `[start_date, end_date[`.

Example:

```text
start = 2026-08-10
end   = 2026-08-15
days  = DATEDIFF(end, start) = 5
```

Overlap query:

```sql
WHERE car_id = :car
  AND status IN ('pending', 'confirmed')
  AND start_date < :requested_end
  AND end_date > :requested_start
```

Boundary example:

- existing `[10, 15[`;
- requested `[15, 18[`;
- `existing.end_date > requested.start_date` is false (`15 > 15`), so there is no overlap.

## 7. Concurrency and transactions

The application locks the `cars` row, not every date row. This provides one clear serialization point per physical vehicle.

```text
Transaction A locks car 5 -> checks -> inserts -> commits
Transaction B waits       -> checks again -> finds A -> HTTP 409
```

InnoDB releases the lock at commit/rollback. The check must be inside the transaction; a check only before the lock is unsafe.

## 8. Indexes and performance

Important indexes support:

- user email uniqueness and login;
- active/role/status filters;
- public brand/model/price search;
- car/status/date overlap checks;
- ordered car images;
- user notification chronology;
- reservation/payment ownership joins.

Eloquent eager loading retrieves category/images/user/car relationships in batches. Dashboard analytics uses aggregate queries and preloaded period records instead of one query per car.

## 9. Migrations, data safety, and seeders

Migrations are append-only schema history for a populated project. Safe commands:

```powershell
docker compose exec api php artisan migrate:status
docker compose exec api php artisan migrate
```

Unsafe for the development database:

```text
php artisan migrate:fresh
php artisan db:wipe
docker compose down -v
```

The demo seeders are idempotent and may be used on a correctly targeted local database. Always verify `DB_DATABASE=astra` before inspecting development data and never point an isolated test command at it.

## 10. Connections from Docker and Windows

Inside the Laravel container:

```text
DB_HOST=database
DB_PORT=3306
DB_DATABASE=astra
```

From DBeaver on Windows:

```text
Host: 127.0.0.1
Port: 3306
Database: astra
```

`database` is Docker's internal DNS name. `127.0.0.1` is the host-side published port.

## 11. Safe inspection examples

```sql
SELECT role, COUNT(*) FROM users GROUP BY role;
SELECT status, COUNT(*) FROM reservations GROUP BY status;
SELECT operational_status, COUNT(*) FROM cars GROUP BY operational_status;
SELECT COUNT(*) FROM payments WHERE status = 'paid';
```

These reads help explain the project without changing data.
