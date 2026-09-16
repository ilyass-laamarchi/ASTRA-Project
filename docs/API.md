# ASTRA REST API Reference

Local base URL: `http://localhost:8000/api`. JSON is the normal request/response format. Protected routes use `Authorization: Bearer <Sanctum token>`.

## Public endpoints

| Method | URL | Purpose | Controller |
|---|---|---|---|
| POST | `/register` | Create a Client account only | `AuthController@register` |
| POST | `/login` | Authenticate an active account | `AuthController@login` |
| POST | `/forgot-password` | Request a reset link without account disclosure | `AuthController@forgotPassword` |
| POST | `/reset-password` | Apply token and revoke sessions | `AuthController@resetPassword` |
| GET | `/auth/google/redirect` | Obtain Google OAuth URL | `AuthController@googleRedirect` |
| GET | `/auth/google/callback` | Complete Google OAuth | `AuthController@googleCallback` |
| GET | `/cars` | List active available cars; accepts date/filter query | `CarController@index` |
| GET | `/cars/{car}` | Public car details | `CarController@show` |
| GET | `/categories` | Active public categories | `CarController@categories` |
| GET/POST | `/cars/{car}/availability` | Check one date interval | `CarController@availability` |
| GET | `/cars/{car}/unavailable-periods` | Safe pending/confirmed periods | `CarController@unavailablePeriods` |
| POST | `/contact` | Store a public contact inquiry | `ContactController@store` |
| POST | `/payments/webhook` | Receive signed Stripe events | `PaymentController@webhook` |

## Any authenticated role

| Method | URL | Purpose |
|---|---|---|
| GET | `/me` | Current safe user record |
| PATCH | `/profile` | Update own identity/contact fields |
| POST | `/profile/avatar` | Upload own avatar |
| PUT | `/password` | Change own password |
| GET | `/notifications` | Own private notifications |
| PATCH | `/notifications/read-all` | Mark all own notifications read |
| PATCH | `/notifications/{notification}/read` | Mark one owned notification read |
| GET/PUT | `/notification-preferences` | Read/update own preferences |
| POST | `/logout` | Revoke current token |

## Client endpoints

| Method | URL | Purpose |
|---|---|---|
| GET | `/my-reservations` | Own reservations only |
| POST | `/my-reservations` | Create server-priced pending reservation |
| GET | `/my-reservations/{reservation}` | Own reservation details |
| PATCH | `/my-reservations/{reservation}/cancel` | Cancel an eligible own reservation |
| GET | `/payment-configuration` | Safe checkout readiness/public key |
| POST | `/reservations/{reservation}/checkout` | Checkout for eligible own reservation |
| GET | `/my-payments` | Own payment history |
| GET | `/my-payments/{payment}` | Own payment details |
| GET | `/payments/{payment}/receipt` | Own paid receipt |

## Responsable and Admin endpoints

Use prefix `/owner` for Responsable (`owner` or admin accepted) and `/admin` for admin-only group.

| Method | Suffix | Purpose |
|---|---|---|
| GET | `/dashboard` | Role-specific operations/analytics |
| GET/POST | `/categories` | List/create categories |
| PUT | `/categories/{category}` | Update category |
| PATCH | `/categories/{category}/activation` | Toggle visibility |
| GET/POST | `/cars` | List/create cars |
| GET/PUT | `/cars/{car}` | Show/update car |
| PATCH | `/cars/{car}/activation` | Archive/activate car |
| PATCH | `/cars/{car}/operational-status` | Set available/maintenance/unavailable |
| POST | `/cars/{car}/images` | Upload images |
| PATCH | `/cars/{car}/images/{image}/primary` | Set primary image |
| PATCH | `/cars/{car}/images/reorder` | Save image ordering |
| DELETE | `/cars/{car}/images/{image}` | Delete car-owned image |
| GET | `/clients` | Client directory |
| GET | `/clients/{user}` | Staff-visible client detail |
| GET | `/reservations` | Staff reservation register |
| GET | `/reservations/{reservation}` | Staff reservation detail |
| PATCH | `/reservations/{reservation}/confirm` | Pending -> confirmed |
| PATCH | `/reservations/{reservation}/reject` | Pending -> rejected with reason |
| PATCH | `/reservations/{reservation}/cancel` | Cancel eligible booking |
| PATCH | `/reservations/{reservation}/complete` | Confirmed -> completed |
| GET | `/payments` | Staff payment register |
| GET | `/payments/{payment}` | Staff payment detail |

## Admin-only endpoint suffixes

| Method | URL | Purpose |
|---|---|---|
| GET/POST | `/admin/staff` | List/create Responsables |
| GET/PUT | `/admin/staff/{user}` | Show/update Responsable |
| PATCH | `/admin/staff/{user}/activation` | Suspend/activate Responsable |
| PATCH | `/admin/clients/{user}/activation` | Suspend/activate Client |
| POST | `/admin/payments/{payment}/refund` | Provider refund for paid payment |
| GET/PUT | `/admin/settings` | Read/update agency settings |
| GET | `/admin/settings/payment-status` | Safe Stripe configuration status |

## Request example

```http
POST /api/my-reservations
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "car_id": 5,
  "start_date": "2026-09-10",
  "end_date": "2026-09-15",
  "client_message": "Arrivée à 10h"
}
```

The browser does not send trusted duration, daily price, total, status, or payment success.

## Status codes

| Code | Meaning in ASTRA |
|---|---|
| 200 | Successful read/update/action |
| 201 | Registration/reservation/resource created |
| 401 | Missing or invalid authentication |
| 403 | Role/ownership/account forbidden |
| 404 | Missing or outside visible scope |
| 409 | Date/payment/business conflict |
| 422 | Validation or invalid status transition |
| 503 | Google/Stripe external configuration absent |

Run `docker compose exec api php artisan route:list` for the exact generated route inventory.
