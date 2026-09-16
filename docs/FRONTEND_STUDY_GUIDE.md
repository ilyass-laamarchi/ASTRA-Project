# ASTRA Frontend Study Guide

This guide explains how the Vue 3 application renders ASTRA and communicates with Laravel.

## 1. Vue application structure

`main.js` creates the Vue app and installs Pinia and Vue Router. `App.vue` restores saved authentication and displays the active route through `RouterView`.

```text
route -> view -> reusable component -> store/composable/helper -> Axios -> Laravel
```

Vue uses `<script setup>`, the concise form of the Composition API.

## 2. Composition API basics

- `ref(value)` creates a reactive value accessed as `.value` in JavaScript.
- `computed(() => ...)` derives a value and updates it when dependencies change.
- `onMounted()` runs after a component enters the page.
- `onUnmounted()` removes timers/listeners/subscriptions.
- `defineProps()` declares data received from a parent.

Templates automatically unwrap refs, so `loading.value` in JavaScript is simply `loading` in HTML.

## 3. Views and components

A **view** represents a route. A **component** represents reusable or focused UI inside a view.

- `HomeView`: public marketing and fleet highlights.
- `CarsView`: searchable/paginated catalogue.
- `CarDetailView`: one vehicle plus availability calendar.
- `AuthView`: login/register in one visual system.
- `StaticView`: about/contact presentations.
- `WorkspaceView`: protected shell that selects client, Owner, or Admin modules.

The approved premium visual pages remain cohesive. Splitting CSS-heavy presentation sections into dozens of tiny files would make design maintenance harder without improving business logic.

## 4. Vue Router

`router/index.js` defines public, guest-only, authenticated, and role-specific routes. Views are lazy loaded, so their JavaScript downloads only when needed.

The navigation guard:

1. restores auth if necessary;
2. sends unauthenticated users to login for protected routes;
3. sends an authenticated user away from guest-only auth pages;
4. compares route role metadata with `auth.user.role`;
5. routes a user to their own dashboard if the role is wrong.

This improves user experience. Laravel still performs the real authorization.

## 5. Pinia authentication store

`stores/auth.js` holds:

- `token`;
- `user`;
- `loading`;
- computed `isAuthenticated` and role helpers;
- login/register/restore/logout actions;
- `updateUser` for profile/avatar changes.

The token and safe user record are mirrored in local storage for refresh persistence. Axios reads the token for each request. A 401 clears stale authentication.

Example:

```text
SettingsManager uploads avatar
  -> Laravel returns refreshed user
  -> auth.updateUser(user)
  -> header and profile recompute immediately
```

## 6. One Axios client

`lib/api.js` creates the single configured Axios instance.

Request interceptor:

- adds `Accept: application/json`;
- adds `Authorization: Bearer ...` when a token exists.

Response interceptor:

- returns the useful JSON body consistently;
- clears invalid auth on 401;
- preserves the Axios error so components can display the server message.

Components call relative endpoints such as:

```js
await api.get('/cars')
await api.post('/my-reservations', payload)
```

The base URL comes from `VITE_API_BASE_URL`, normally `http://localhost:8000/api`.

## 7. Public fleet flow

1. Home/Cars view calls `GET /cars` and `GET /categories`.
2. Laravel returns active cars with category and images.
3. Vue stores arrays in refs.
4. Computed values apply local presentation filters or pagination.
5. Router links open `/cars/:id`.
6. Car detail loads the record and renders `AvailabilityCalendar`.

Vehicle image paths come from MySQL relations. Approved fallback images are used only when a car has no valid primary image.

## 8. Reservation UI flow

`AvailabilityCalendar.vue`:

- loads blocked periods for twelve months;
- accepts date query parameters;
- prevents invalid/past/blocked selections;
- calculates a display quote using the same date helper;
- sends only car, dates, and optional message;
- handles 401/409/422 messages professionally;
- refreshes dates through `useAvailabilitySync`.

Laravel recalculates everything. Frontend price is informational, not trusted.

`lib/reservations.js` keeps one canonical client endpoint and payload shape so tests and UI do not drift.

## 9. Date helpers

Rental dates are calendar dates, not moments in UTC. `lib/dates.js` parses/prints `YYYY-MM-DD` without timezone shifting. For display, components append midday where necessary before `Intl.DateTimeFormat`.

Duration follows `[start, end[`:

```text
2026-08-10 -> 2026-08-15 = 5 days
```

## 10. Client dashboard

The client dashboard receives only authenticated client endpoints:

- `/my-reservations`;
- `/my-payments` or reservation payment state;
- public `/cars` for the small available fleet section.

`lib/clientDashboard.js` derives personal counts, next booking, recent bookings, and valid actions. It never consumes staff dashboard analytics.

Actions appear only when valid: view, pay a confirmed/unpaid eligible booking, or cancel an allowed booking.

## 11. Staff workspace

`WorkspaceView.vue` is the shared shell: sidebar, header, notification panel, responsive menu, and selected module. It maps role/path to:

- ClientDashboard or reservation/payment/profile modules;
- Owner DashboardHome and operational managers;
- Admin DashboardHome plus StaffManager and SettingsManager features.

Managers call the matching `/owner/...` or `/admin/...` endpoint. The backend rejects any role mismatch.

## 12. Media and profile avatar

`lib/media.js` handles three cases:

- empty path -> no image/fallback initials;
- absolute browser URL -> keep it;
- Laravel relative storage path -> prefix the API origin and `/storage/`.

The shared Pinia user means both profile card and top-right avatar update after upload.

## 13. Realtime and fallback refresh

`lib/realtime.js` creates Echo only when Reverb values exist. `useAvailabilitySync`:

1. subscribes to the car availability channel;
2. refreshes on a Laravel event;
3. refreshes when the tab becomes visible or focused;
4. performs a slow polling fallback;
5. removes listeners/timers/channels on unmount.

Realtime tells the UI *when* to refresh. The API response remains authoritative.

## 14. Error handling

Components maintain local error state and show French user-facing messages. They do not expose stack traces or secrets. Debug `console.log`/`console.error` calls were removed from active application code; errors are either displayed or handled by the shared authentication response logic.

Common handling:

- 401 -> login/session reset;
- 403 -> own dashboard or access message;
- 409 -> dates/payment state changed;
- 422 -> validation message;
- 503 -> Google/Stripe not configured.

## 15. Logo and assets

`AstraLogo.vue` centralizes the brand source:

- original dark/blue transparent logo on light surfaces;
- white/soft-blue transparent logo on dark, blue, or photo surfaces.

No white rectangle is added behind the logo. Images remain in `public/assets` because the browser must request them directly. Fleet images referenced by MySQL are preserved.

## 16. Frontend testing

Vitest is for quick logic:

- dates and durations;
- media URLs;
- reservation request shape;
- client summaries/actions;
- route metadata/guards.

Playwright is for integrated behavior:

- public navigation and fleet images;
- auth/role restrictions;
- client/staff dashboards;
- reservation workflow and concurrency;
- responsive visual smoke checks.

Commands:

```powershell
docker compose exec frontend npm test -- --run
docker compose exec frontend npm run build
docker compose exec frontend npm run test:e2e
```

## 17. How to explain one frontend request

Use this example:

> The client selects dates in `AvailabilityCalendar.vue`. The component uses the shared reservation payload helper and Axios client. Axios adds the Sanctum token and posts JSON to `/api/my-reservations`. Laravel validates and saves the booking, then returns JSON. The component routes to the protected client reservations page. Realtime events tell other open calendars to refresh.

That one example demonstrates components, helpers, Axios, tokens, REST, backend rules, router, state, and realtime.
