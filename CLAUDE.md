# SyncRide — System Overview

Fleet and transfer management SaaS. Three experiences on one system: **Admin back-office** (desktop), **Driver App** (Android), **Partner Portal** (agencies/hostels). Public repo: `1231366/SyncRide`.

## Two codebases

1. **Web app** — this repo (`SRMT`). PHP 8.2 backend + server-rendered views, serves the API the Android app talks to.
2. **Android app** — separate repo at `/Users/t/Documents/syncride-android/`. Capacitor WebView pointed at prod (`https://syncride.wmservers.pt/SRMT/public/`) plus native Java for background GPS and FCM push. One codebase, no separate native rewrite.

Changes to driver-facing views/API in this repo affect the Android app live (it just loads the prod URL), except for the native Java pieces (location service, push handling), which live only in the android repo and need a rebuild + Play Store release.

## Web app architecture (this repo)

Custom MVC, no framework. Thin Controllers → Repositories → Services → Models. PSR-4 autoload (`App\` → `app/`, `Cron\` → `cron/`). PDO with prepared statements everywhere. Multi-tenant: every query scoped by `company_id`.

```
app/Http/Controllers/   Admin/, Api/, Auth/, Driver/, Partner/, SuperAdmin/ — thin, delegate to Services/Repositories
app/Models/              Company, User, Service (=ride/trip), Vehicle, Expense, LiveLocation, ChatMessage
app/Repositories/        DB access per entity (UserRepository, ServiceRepository, VehicleRepository, PricingRepository, ...)
app/Services/            Business logic: PricingEngine, ExcelServiceImporter, XmlVoucherImporter, FCMSender,
                          Mailer/NoShowMailer/ScheduleMailer/VoucherMailer, StripeService, NoShowReportGenerator
app/Auth/                AuthController (login/session)
app/Support/             Env, Database, Session — env-driven bootstrap
cron/                    Scheduled jobs (Jobs/ = current, Legacy/ = old scripts still bridged)
public/{admin,driver,partner,api,superadmin,auth,webhook}/   Web-accessible entry points (thin shims calling Controllers)
resources/views/          PHP templates, mirrors public/ role folders + layouts/
database/                 Incremental SQL migrations (2026_*.sql)
docs/                     PLANO_PRTOURS.md, deploy guides, architecture.puml
tests/                    PHPUnit
```

**Run locally:** `composer install && cp .env.example .env && php -S localhost:8000 -t public`. Tests: `composer test`.

### Key domains
- **Rides/Trips** = `Service` model/table — the core transfer booking entity.
- **Pricing** — `PricingEngine` + `PricingRepository` + `ServicePricing`; Excel-driven pricing for PRtours-style operators (see `docs/PLANO_PRTOURS.md`).
- **Tracking** — `Api/LocationController`, `Api/LiveLocationsController`, `LiveLocation` model. Long-polling, ~4s interval, sub-5s latency (no WebSockets needed). Public tracking page lets clients watch their driver live.
- **No-shows** — driver uploads photo evidence (base64) → `NoShowsController` / `NoShowMailer` / `NoShowReportGenerator` → PDF report + email.
- **Billing/Trials** — self-service register + Stripe-backed 7-day trial, `BillingController`, `StripeService`, `BillingGate` logic (see memory `project_syncride_trials`).
- **Imports** — `ExcelServiceImporter` (PRtours/operator Excel) and `XmlVoucherImporter` (XML voucher feeds) bulk-create Services.
- **WhatsApp dispatch** — `cron/Jobs/WhatsappAgendaJob` sends drivers their daily schedule automatically; separate `wpp-service/` handles the WhatsApp integration.
- **Multi-tenancy** — `Company` model, every table/query carries `company_id`; SuperAdmin controllers manage companies across tenants.

### Security / infra notes
- CSRF tokens, ownership guards on all mutations, bcrypt, `session_regenerate_id`, output escaping.
- Prod runs behind mod_security (shared hosting). POST bodies to `Api/*` endpoints must be **base64-shielded** (`p` field, decoded server-side) or the WAF blocks them — see memory `project_wmservers_waf`.
- Legacy pages under `public/{admin,driver,partner,api}/*.php` are being migrated one at a time to Controller+View pairs — see `.claude/MIGRATION_PLAN.md` (gitignored, local only) for status and approach.

## Android app (`/Users/t/Documents/syncride-android/`)

- **Capacitor 6** WebView shell (`www/index.html` is just a loader) — `capacitor.config.json` points `server.url` at prod, so the actual UI is the same PHP-rendered `driver/*` views from this repo.
- **Native Java** (`android/app/src/main/java/pt/syncride/app/`, source mirrored in `android-src/`):
  - `MainActivity.java` — Capacitor bridge entry point.
  - `LocationService.java` / `BackgroundGeolocation.java` — foreground GPS tracking service, keeps streaming location even when the WebView is backgrounded or Waze is open.
  - `SyncRideMessagingService.java` — Firebase Cloud Messaging (push notifications, ride reminders 30 min before departure).
  - `BootReceiver.java` — restarts tracking service on device boot.
- Google Play app ID: `pt.syncride.app`. Firebase config: `google-services.json` (web repo) / FCM service account key present in repo root.
- `KEYSTORE_CREDENTIALS.md` (gitignored) holds the release signing key info.
- Sync web assets → native project: `npm run sync` (`npx cap sync android`). Open in Android Studio: `npm run open`.

## Where to look first
- Active feature work: `docs/PLANO_PRTOURS.md` (Excel import/pricing — phased plan, check which phase is done).
- Legacy migration status/approach: `.claude/MIGRATION_PLAN.md`.
- Trials/billing flow details: ask for memory `project_syncride_trials` or read `BillingController` + `StripeService`.
