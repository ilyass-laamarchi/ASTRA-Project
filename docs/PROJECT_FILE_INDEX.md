# ASTRA Project File Index

This is a quick map of every meaningful custom source/configuration file. Generated dependencies, caches, lockfile internals, build output, and binary assets are intentionally excluded.

## Root and runtime

| Path | Purpose | Important content | Connection |
|---|---|---|---|
| `.gitignore` | Keeps local/generated files out of source control | env, vendor, node_modules, build and test output | Entire repository |
| `compose.yaml` | Local four-service environment | database, api, reverb, frontend, volumes | Docker Desktop |
| `README.md` | Short entry point | architecture, setup, tests, docs | New developers |
| `backend/Dockerfile` | Laravel runtime image | PHP extensions, Composer, entry command | `api`, `reverb` |
| `backend/Dockerfile.test` | Isolated backend test image | testing dependencies | PHPUnit verification |
| `backend/composer.json` | Backend dependencies/scripts | Laravel, Sanctum, Socialite, Reverb, Stripe | Composer |
| `backend/phpunit.xml` | Safe test environment | SQLite memory database, testing variables | PHPUnit |
| `frontend/package.json` | Frontend dependencies/scripts | Vue, Pinia, Axios, Echo, tests | npm |
| `frontend/vite.config.js` | Vite development/build setup | Vue plugin, host config | frontend container |
| `frontend/vitest.config.js` | Unit test setup | jsdom and test include rules | Vitest |
| `frontend/playwright.config.js` | Browser test setup | base URL, projects, artifacts | Playwright |
| `frontend/tailwind.config.js` | Tailwind source scan/theme | Vue and index paths | CSS build |
| `frontend/postcss.config.js` | CSS transforms | Tailwind, Autoprefixer | Vite |
| `frontend/index.html` | SPA HTML entry | root mount and metadata | `main.js` |

## Backend application

### HTTP layer

| Path | Purpose | Important functions | Dependencies / connection |
|---|---|---|---|
| `app/Http/Controllers/Controller.php` | Shared controller base | none | All API controllers |
| `app/Http/Controllers/Api/AuthController.php` | Auth, profile, avatar, password, OAuth | `register`, `login`, `me`, `update`, `avatar`, `password`, OAuth methods | User, Sanctum, Socialite, storage |
| `app/Http/Controllers/Api/CarController.php` | Public fleet API | `index`, `show`, `categories`, `availability`, `unavailablePeriods` | CarResource, availability service |
| `app/Http/Controllers/Api/ContactController.php` | Public contact submission | `store` | ContactInquiry, NotificationService |
| `app/Http/Controllers/Api/ManagementController.php` | Staff CRUD and dashboards | dashboard/category/car/image/client/staff methods | Models, analytics, storage |
| `app/Http/Controllers/Api/NotificationController.php` | Private notification API | `index`, `read`, `readAll`, preferences | AstraNotification, User |
| `app/Http/Controllers/Api/PaymentController.php` | Payment HTTP workflow | configuration, checkout, webhook, lists, receipt, refund | PaymentService, ownership |
| `app/Http/Controllers/Api/ReservationController.php` | Client/staff reservation API | `store`, `mine`, `confirm`, `reject`, `cancel`, `complete` | transaction, availability/status services |
| `app/Http/Controllers/Api/SettingsController.php` | Admin settings and provider readiness | `show`, `update`, `paymentStatus` | ApplicationSetting, config |
| `app/Http/Middleware/EnsureRole.php` | Backend role enforcement | `handle` | authenticated User role |
| `app/Http/Requests/RegisterRequest.php` | Public registration validation | `authorize`, `rules` | AuthController |
| `app/Http/Requests/StoreReservationRequest.php` | Client/date/car validation | `authorize`, `rules`, `messages` | ReservationController |
| `app/Http/Resources/CarResource.php` | Safe public car JSON | `toArray` | Car relationships |
| `app/Http/Resources/ReservationResource.php` | Role-aware reservation JSON | `toArray` | Reservation relationships |

### Models and business logic

| Path | Purpose | Important functions | Dependencies / connection |
|---|---|---|---|
| `app/Models/User.php` | All account roles | casts, full name, reservations/payments/notifications, `isStaff` | users table, Sanctum |
| `app/Models/Category.php` | Fleet category | casts, `cars` | categories table |
| `app/Models/Car.php` | Fleet vehicle | casts, category/images/primaryImage/reservations | cars table |
| `app/Models/CarImage.php` | Ordered vehicle media | casts, `car` | car_images table |
| `app/Models/Reservation.php` | Server-priced booking and status constants | casts, user/car/payments | reservations table |
| `app/Models/Payment.php` | Provider payment lifecycle | casts, reservation/user | payments table |
| `app/Models/AstraNotification.php` | Private notification | casts, user | astra_notifications table |
| `app/Models/ApplicationSetting.php` | Key/value agency setting | helper accessors | application_settings table |
| `app/Models/ContactInquiry.php` | Contact workflow record | casts | contact_inquiries table |
| `app/Services/CarAvailabilityService.php` | Half-open availability rules | `isAvailable`, `overlappingQuery`, periods | Car, Reservation |
| `app/Services/ReservationStatusService.php` | Allowed reservation transitions | confirm/reject/cancel/complete transition | transaction, events, notifications |
| `app/Services/PaymentService.php` | Trusted checkout/webhook/refund logic | configuration, checkout, webhook, refund | gateway, locks, events |
| `app/Services/StripePaymentGateway.php` | Stripe SDK adapter | createCheckout, parseWebhook, refund | Stripe PHP SDK |
| `app/Contracts/PaymentGatewayInterface.php` | Testable provider boundary | checkout/webhook/refund signatures | PaymentService |
| `app/Services/NotificationService.php` | Preference-aware notification creation | notify user/staff | events, notification model |
| `app/Services/DashboardAnalyticsService.php` | Role-specific dashboard data | admin/owner analyses and helpers | aggregate/eager-loaded queries |

### Events, provider, and maintenance command

| Path | Purpose | Important functions | Connection |
|---|---|---|---|
| `app/Events/CarAvailabilityChanged.php` | Broadcast car date changes | channel/name/payload | Reverb and availability composable |
| `app/Events/ReservationStatusChanged.php` | Broadcast reservation state | channel/name/payload | Workspaces and notifications |
| `app/Events/PaymentStatusChanged.php` | Broadcast verified payment state | channel/name/payload | Payment views |
| `app/Events/NotificationCreated.php` | Broadcast private notification | private channel/payload | Workspace header |
| `app/Providers/AppServiceProvider.php` | Application bindings | `register`, `boot` | Payment interface -> Stripe adapter |
| `app/Console/Commands/RepairOverlappingReservations.php` | Audits/repairs legacy overlaps | `handle` and conflict helpers | Reservation, events; dry-run first |

## Backend routes and bootstrap

| Path | Purpose | Important content | Connection |
|---|---|---|---|
| `routes/api.php` | Complete REST contract | public, client, owner, admin groups | Controllers/middleware |
| `routes/channels.php` | Broadcast authorization | private user channels | Sanctum/Reverb |
| `routes/web.php` | Service root response | JSON service status | `/` on API host |
| `routes/console.php` | Console route placeholder | intentional explanation only | Artisan |
| `bootstrap/app.php` | Laravel application pipeline | routes, middleware alias, exception behavior | Framework bootstrap |
| `bootstrap/providers.php` | Provider registry | AppServiceProvider | Framework bootstrap |
| `public/index.php` | HTTP front controller | boots Laravel | web server |

## Backend configuration

| Path | Purpose | Key connection |
|---|---|---|
| `config/app.php` | app name, locale, timezone | `Africa/Casablanca` |
| `config/auth.php` | guards/providers | User authentication |
| `config/broadcasting.php` | Reverb broadcast connection | Events |
| `config/cache.php` | cache stores | Laravel services |
| `config/cors.php` | allowed frontend origins | Browser -> API |
| `config/database.php` | MySQL and test database connections | Eloquent |
| `config/filesystems.php` | public upload disk and URL | profile/car images |
| `config/logging.php` | safe server logs | debugging |
| `config/mail.php` | mail transport | password reset architecture |
| `config/queue.php` | job backend | Laravel jobs/events |
| `config/reverb.php` | WebSocket app/server values | Reverb service |
| `config/services.php` | Google and Stripe variables | Socialite/payment gateway |
| `config/session.php` | session settings | Laravel framework |

## Database schema and fixtures

| Path | Purpose | Connection |
|---|---|---|
| `database/migrations/0001_01_01_000000_create_users_table.php` | users, password resets, sessions | Auth |
| `database/migrations/0001_01_01_000001_create_cache_table.php` | cache/locks | Framework |
| `database/migrations/0001_01_01_000002_create_jobs_table.php` | queues/failures/batches | Framework |
| `database/migrations/2026_08_04_000050_create_personal_access_tokens_table.php` | Sanctum tokens | API auth |
| `database/migrations/2026_08_04_000100_create_astra_business_tables.php` | categories, cars, images, reservations, payments | Core domain |
| `database/migrations/2026_08_11_130000_create_settings_notifications_and_profile_fields.php` | avatar, preferences, settings, notifications | Profile/system |
| `database/migrations/2026_08_11_131000_create_contact_inquiries_table.php` | contact messages | Public contact |
| `database/seeders/DatabaseSeeder.php` | idempotent demo orchestration and history | all domain models |
| `database/seeders/DemoFleetSeeder.php` | persistent Tanger fleet/images | categories/cars/images |
| `database/seeders/AgencySettingsSeeder.php` | default agency settings | application_settings |
| `database/factories/UserFactory.php` | isolated account fixtures | backend tests |
| `database/factories/CategoryFactory.php` | isolated category fixtures | backend tests |
| `database/factories/CarFactory.php` | isolated car fixtures | backend tests |
| `database/factories/ReservationFactory.php` | isolated booking fixtures | backend tests |

## Backend tests

| Path | Purpose | Important coverage |
|---|---|---|
| `tests/TestCase.php` | Laravel test base | application bootstrap |
| `tests/Feature/AstraApiTest.php` | API/auth/security suite | avatar, isolation, roles, OAuth, pricing |
| `tests/Feature/AvailabilitySystemTest.php` | reservation correctness suite | dates, blockers, 409, events, checkout rules |
| `tests/Feature/DashboardAnalyticsTest.php` | role-specific analytics | paid revenue, owner payload, client denial |
| `tests/Feature/FunctionalCompletionTest.php` | supporting features | profile, reset, notifications, settings, contact, seeders |

## Frontend application

### Entry, routing, and shared libraries

| Path | Purpose | Important functions | Connection |
|---|---|---|---|
| `src/main.js` | Creates Vue app | Pinia/router registration | `App.vue` |
| `src/App.vue` | Root outlet and auth restore | mounted restore | router/auth store |
| `src/router/index.js` | Route table and guards | lazy imports, `beforeEach` | all views/auth |
| `src/stores/auth.js` | Token and current user | authenticate/login/register/restore/logout/update | API/local storage |
| `src/lib/api.js` | Single Axios instance | request/response interceptors | every HTTP consumer |
| `src/lib/dates.js` | Date-only conversions | ISO parsing, duration, today | reservations/tests |
| `src/lib/media.js` | Public media URLs | `publicMediaUrl` | avatars/images |
| `src/lib/reservations.js` | Canonical client booking request | endpoint/payload helper | calendar/tests |
| `src/lib/clientDashboard.js` | Personal dashboard derivations | summaries, next booking, actions | ClientDashboard |
| `src/lib/realtime.js` | Lazy Echo connection | client creation/disconnect behavior | availability/workspace |
| `src/composables/useAvailabilitySync.js` | Realtime/fallback refresh lifecycle | subscribe, focus, polling cleanup | AvailabilityCalendar |
| `src/style.css` | Global tokens and shared layout | typography/theme/responsive rules | all views |

### Route views

| Path | Purpose | Important functions | Connection |
|---|---|---|---|
| `src/views/HomeView.vue` | Public landing page | fleet load, FAQ, carousel | cars/categories API |
| `src/views/CarsView.vue` | Public catalogue | load, clear filters, pagination | cars API/router query |
| `src/views/CarDetailView.vue` | Vehicle information and booking | load car | AvailabilityCalendar |
| `src/views/AuthView.vue` | Login and registration | submit, Google redirect, password visibility | auth store/API |
| `src/views/OAuthCallbackView.vue` | Completes OAuth handoff | `finishOAuth` | auth store/router |
| `src/views/PasswordResetView.vue` | Password recovery forms | submit flow | auth API |
| `src/views/PaymentResultView.vue` | Checkout return state | safe status display | payment workspace |
| `src/views/StaticView.vue` | About/contact route content | topic and contact submission | contact API |
| `src/views/LegalView.vue` | Current legal/privacy copy | route-derived content | public router |
| `src/views/WorkspaceView.vue` | Protected role shell | module selection, notifications, responsive nav | auth, admin/client modules |

### Reusable and role components

| Path | Purpose | Important functions | Connection |
|---|---|---|---|
| `src/components/AstraLogo.vue` | Centralized responsive logo variants | variant selection | public/auth/workspace/footer |
| `src/components/AvailabilityCalendar.vue` | Calendar and booking | availability, selection, quote, submit | API/realtime/router |
| `src/components/CarCard.vue` | Reusable vehicle card | presentation only | catalogue/dashboard |
| `src/components/CarEditor.vue` | Vehicle editor flow | previews, existing images, save | staff API |
| `src/components/client/ClientDashboard.vue` | Real customer portal | load, image/date/action methods | client APIs/helpers |
| `src/components/admin/DashboardHome.vue` | Admin/owner dashboard | load, chart/format/navigation helpers | dashboard API |
| `src/components/admin/CarManager.vue` | Staff fleet management | load/edit/images/save/activation | management API |
| `src/components/admin/CategoryManager.vue` | Category management | load/edit/save/toggle | management API |
| `src/components/admin/ClientManager.vue` | Client directory/details | load/show/toggle | management API |
| `src/components/admin/ReservationManager.vue` | Reservation workflow | load, transition, checkout | reservation/payment API |
| `src/components/admin/PaymentManager.vue` | Payment register/refund | load, format, refund | payment API |
| `src/components/admin/SettingsManager.vue` | Profile/preferences/admin settings | load and save methods, avatar | profile/settings API |
| `src/components/admin/StaffManager.vue` | Responsable management | load/create/edit/activation | admin API |

## Frontend tests

| Path | Purpose |
|---|---|
| `src/lib/dates.test.js` | Date and duration unit tests |
| `src/lib/media.test.js` | Stored-media URL unit tests |
| `src/lib/reservations.test.js` | Booking endpoint/payload unit tests |
| `src/lib/clientDashboard.test.js` | Personal summary/action unit tests |
| `src/router/router.test.js` | Route metadata/guard unit tests |
| `tests/e2e/public.spec.js` | Public navigation smoke flow |
| `tests/e2e/fleet.spec.js` | Catalogue and real fleet images |
| `tests/e2e/auth-security.spec.js` | Registration role and guarded route security |
| `tests/e2e/client-dashboard.spec.js` | Client-only dashboard UI |
| `tests/e2e/reservation-route.spec.js` | Booking handoff to client route |
| `tests/e2e/concurrency.spec.js` | Two-client hold/409 and realtime behavior |
| `tests/e2e/workspace.spec.js` | Client and staff workflow states |
| `tests/e2e/staff-dashboard.spec.js` | Admin vs Responsable payload/UI |
| `tests/e2e/profile-payment.spec.js` | Optional real-token avatar/payment check |
| `tests/e2e/visual.spec.js` | Responsive pages and image availability |

## Documentation

| Path | Purpose |
|---|---|
| `docs/ASTRA_COMPLETE_GUIDE.md` | Master end-to-end study document |
| `docs/PROJECT_FILE_INDEX.md` | This quick source map |
| `docs/BACKEND_STUDY_GUIDE.md` | Laravel-focused learning guide |
| `docs/FRONTEND_STUDY_GUIDE.md` | Vue-focused learning guide |
| `docs/DATABASE_STUDY_GUIDE.md` | Actual schema and relationship guide |
| `docs/PRESENTATION_CHEAT_SHEET.md` | Short oral-presentation revision sheet |
| `docs/API.md` | Current endpoint reference |
| `docs/TESTING.md` | Safe verification commands and scopes |
