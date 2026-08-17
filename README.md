<div align="center">

# 📝 Noteket

**A collaborative notes & team workspace with wallets, OTP-verified transactions, PayOS top-ups, and Google sign-in.**

</div>

Noteket combines personal note-taking with lightweight organization management, a **wallet/transaction system** (user ↔ user, user ↔ organization, organization ↔ user) secured by **email-OTP confirmation**, a **theme marketplace**, **point top-ups through PayOS**, and **Google OAuth login**.

---

## ✨ Features

| Area | Highlights |
| --- | --- |
| **Notes** | Create, edit, delete, mark-as-done / undo, filter (all / done / not-done / mine / shared), paginated queue-ordered feed, replies, and **share by link** — the recipient opens the note's share URL and gains access instantly (no email round-trip) |
| **Organizations** | Create & manage orgs, **invite by link** (visitor opens the org share URL, gets a pending-member screen with accept/decline), remove members, host handover with OTP confirmation, `/invite/{token}` signup for unregistered invitees |
| **Payments** | Top up points through **PayOS**: point→VND conversion preview, hosted checkout redirect, signature-verified webhook that credits points exactly once (replay-safe, locked transaction), payment history and bill views |
| **Transactions** | Send points between users/orgs with decimal balances, 6-digit email OTP (10-minute expiry, 5 attempts max), full history, cancel flow |
| **Themes** | Marketplace of personal and organization themes, purchased via OTP-verified wallet transactions, then **applied** — a theme repaints the UI via CSS variables and changes the card drag feel |
| **Auth & Accounts** | Signup/login/logout, **Google OAuth** (links to an existing account with the same email instead of duplicating it), password reset with OTP, profile & avatar settings (ImageKit), session regeneration on OAuth login |
| **Quality** | **95 Pest tests / 354 assertions, 0 skipped**, PHPStan static analysis, Laravel Pint code style; the test gate is **enforced in CI on every branch** |

---

## 🧱 Tech stack

- **Laravel 13** (13.23) on **PHP 8.3+** (8.5 matches CI and the Dockerfile)
- **PostgreSQL** as the default connection in `.env.example`; SQLite works out of the box for local dev and is what the test suite uses
- **Blade** templates, Bootstrap-5-style UI components, bundled with **Vite** (Tailwind 4 tooling included)
- **PayOS PHP SDK v2** (payments), **Laravel Socialite** (Google OAuth), **ImageKit** (avatar/logo uploads)
- **Pest** (tests), **PHPStan** (static analysis), **Pint** (code style)

---

## 📋 Requirements

| Tool | Version |
| --- | --- |
| PHP | ≥ 8.3 (extensions: `pdo_pgsql` or `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `zip`, `fileinfo`, `bcmath`) |
| Composer | 2.x |
| Node.js | 20+ (with npm) |
| Database | PostgreSQL 14+ recommended; SQLite is enough for local dev |

---

## 🚀 Installation

### Quick start (one command)

```bash
composer run setup
```

This runs `composer install`, copies `.env.example` → `.env`, generates an app key, runs migrations, installs npm dependencies, and builds frontend assets.

> `.env.example` defaults to PostgreSQL. For a zero-setup local run, switch to SQLite first: set `DB_CONNECTION=sqlite`, `DB_DATABASE=database/database.sqlite` in `.env` and `touch database/database.sqlite`.

### Manual setup

```bash
# 1. PHP dependencies
composer install

# 2. Environment file & app key
cp .env.example .env
php artisan key:generate

# 3. Database — either PostgreSQL (fill DB_* in .env) or SQLite:
#    DB_CONNECTION=sqlite / DB_DATABASE=database/database.sqlite
touch database/database.sqlite

# 4. Migrations
php artisan migrate

# 5. Frontend assets
npm install
npm run build              # or: npm run dev (watching)
```

> **Docker:** a `Dockerfile` is included for containerized setups. It runs migrations and `composer run dev` inside the container.

---

## ⚙️ Environment configuration

Copy `.env.example` to `.env` and fill in:

| Variable | Default | Notes |
| --- | --- | --- |
| `APP_NAME` | `Laravel` | Set to `Noteket` |
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | `production` / `false` when deployed |
| `APP_KEY` | — | `php artisan key:generate` |
| `APP_URL` | `http://localhost` | **Must be the real domain** — links in queued emails and OAuth/PayOS callbacks are built from it |
| `DB_*` | PostgreSQL | Or switch to SQLite as shown above |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | `database` | Tables come from migrations; the queue needs a [worker](#-queue-worker) |
| `MAIL_*` | `log` | See [Mail configuration](#️-mail-configuration) |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` / `GOOGLE_REDIRECT_URI` | empty | See [Google OAuth](#-google-oauth) |
| `PAYOS_CLIENT_ID` / `PAYOS_API_KEY` / `PAYOS_CHECKSUM_KEY` | empty | See [PayOS payments](#-payos-payments) |
| `IMAGEKIT_PUBLIC` / `IMAGEKIT_PRIVATE` / `IMAGEKIT_ENDPOINT` | empty | From imagekit.io → Developer options; needed for avatar/logo uploads |

---

## 🔑 Google OAuth

1. Create an **OAuth client ID** (type *Web application*) at [console.cloud.google.com](https://console.cloud.google.com) → APIs & Services → Credentials.
2. Add `https://your-domain/oauth/callback/google` to **Authorized redirect URIs** — it must match `GOOGLE_REDIRECT_URI` in `.env` character for character.
3. Fill `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.

Behavior worth knowing (all covered by tests):

- A Google login whose email already belongs to a normal account **links the provider to that account** — it never creates a duplicate.
- Returning Google users keep their local display name; it is not overwritten by the Google profile name on each login.
- The session ID is regenerated after login (session-fixation protection), and the OAuth routes share the `authentication` rate limiter (10 req/min per IP).

---

## 💳 PayOS payments

Point top-up flow: the user enters an amount (1 point = 1,000 VND, previewed live), the backend creates a `Pending` payment and redirects to the PayOS hosted checkout; PayOS then confirms via webhook.

Setup:

1. Get `PAYOS_CLIENT_ID`, `PAYOS_API_KEY`, `PAYOS_CHECKSUM_KEY` from [my.payos.vn](https://my.payos.vn) → your payment channel → API Keys.
2. Register the webhook URL on the PayOS dashboard: **`https://your-domain/point/payment/verify`** (PayOS requires HTTPS and pings the URL once when you save it).

How the webhook endpoint is wired — don't undo these:

- The route is **fixed** (no `{id}`): PayOS only supports one webhook URL, so the payment is looked up by the `orderCode` inside the signature-verified payload.
- It sits **outside `auth`** (the caller is PayOS's server), is **excluded from CSRF** in `bootstrap/app.php`, and is rate-limited by IP via the null-safe `transaction` limiter.
- Credits are **exactly-once**: the `Pending` check happens inside a `lockForUpdate` transaction, so replayed webhooks return 200 (so PayOS stops retrying) without crediting twice. Invalid signatures get 400 before any data is touched.

Before going live, run one manual smoke test on the PayOS sandbox: top up 1 point from the button all the way to the balance increase.

---

## ✉️ Mail configuration

Two delivery modes, and the distinction matters operationally:

| Kind | API | Queue worker needed? |
|---|---|---|
| OTP codes, password resets, accept/decline notifications | `Mail::to(...)->queue()` / `send()` | Some are queued — run a worker to be safe |
| Invitation mails (`OrganizationInvitation`, `Mail40account`) | `Mail::to(...)->queue()` (`ShouldQueue`) | **Yes** |

With `QUEUE_CONNECTION=database` you **must** run a worker or queued mail sits in the `jobs` table forever. For local testing without a worker, set `QUEUE_CONNECTION=sync`.

> ⚠️ The invitation mails render `route('invitation.show')`. The `/invite/{token}` route pair in `routes/web.php` **must stay registered** (it has been accidentally commented out twice) — without it, the worker throws `RouteNotFoundException` and the job lands in `failed_jobs`.

### Local development (default: `log`)

```dotenv
MAIL_MAILER=log
```

Emails go to `storage/logs/laravel.log` — perfect for OTP flows.

### Real email via SMTP

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com          # Gmail, Mailtrap, Mailpit, Brevo…
MAIL_PORT=587                     # 587 (STARTTLS) or 465 (SSL)
MAIL_USERNAME=your-account
MAIL_PASSWORD=your-app-password   # Gmail: use an App Password
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="Noteket"
```

> **Tip:** for a local real inbox, run [Mailpit](https://mailpit.aeroxis.com/) with `MAIL_HOST=127.0.0.1` / `MAIL_PORT=1025`.

---

## 🔄 Queue worker

`QUEUE_CONNECTION=database` is the default; the `jobs` / `failed_jobs` tables come from migrations.

```bash
php artisan queue:work --tries=3 --backoff=10 --timeout=60
```

### Everything at once (local dev)

```bash
composer run dev
```

Starts `php artisan serve`, `php artisan queue:listen --tries=3 --backoff=10 --timeout=60`, and `npm run dev` concurrently.

> **Why these worker flags?** `--tries=3 --backoff=10` retries a failed mail twice (10s apart) before it lands in `failed_jobs`, so one SMTP hiccup doesn't kill an invitation. `--timeout=60` must stay **below** the connection's `retry_after` (90s in `config/queue.php`) — a job that outlives `retry_after` gets re-delivered and would send the same mail twice.

### Production (Supervisor)

```ini
[program:noteket-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/noteket/artisan queue:work --sleep=3 --tries=3 --backoff=10 --timeout=60 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/noteket/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl status
```

Two more things a deployment needs:

1. **Cron for the scheduler** — `queue:prune-failed` only runs if cron calls the scheduler every minute:

   ```cron
   * * * * * cd /var/www/noteket && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **`APP_URL` must be the real domain.** Links inside queued emails are generated by the worker from `APP_URL` — with the default `http://localhost`, every invite link points nowhere.

Failed jobs are logged via a `Queue::failing` listener (`AppServiceProvider`); check `storage/logs/laravel.log`, then `php artisan queue:failed`.

---

## 🖥️ Frontend assets

```bash
npm install
npm run build          # production bundle
npm run dev            # hot-reload dev server
```

> If you see `Unable to locate file in Vite manifest`, run `npm run build` (or `npm run dev`) once.

---

## 🧪 Testing & quality

```bash
php artisan test                    # full Pest suite
php artisan test --filter=Payment   # one area
composer phpstan                    # static analysis
vendor/bin/pint                     # fix code style
```

The suite currently stands at **95 tests / 354 assertions, 0 failures, 0 skipped** (~4s on SQLite in-memory), covering:

- Auth, notes CRUD + access control, share-by-link, replies, filters, pagination
- Organizations, invite-by-link (pending member → accept/decline), host handover
- OTP transactions (wrong-key retry, orphan cleanup on mail failure)
- Themes: buy, apply, CSS-variable rendering, CSS-injection hardening
- **OAuth**: Socialite fully mocked — redirect, first login, returning login, email-linking, rate limit, session regeneration
- **Payments**: PayOS fully mocked — validation, checkout redirect, and the webhook contract (valid credit, replay-safe, bad signature → 400, unknown order → 404, non-success code → no credit)
- Mailables rendered through the real worker path (serialize → unserialize → render)

Because Socialite and PayOS are mocked, tests prove the *logic*, not the *integration* — after changing keys or dashboards, do one manual smoke test of Google login and a sandbox top-up.

CI (`.github/workflows/laravel.yml`) runs on **every branch**: Pint and PHPStan report as warnings, `php artisan test` is **enforced** — a red suite blocks the merge.

---

## 🌿 Branch workflow

| Branch | Purpose |
| --- | --- |
| `main` | Stable, deployable. CI runs on every push/PR. |
| `fix/*` / `feature/*` | Work branches — merge into `main` after the local gate passes. |

Before merging, run the full gate locally:

```bash
php artisan test && composer phpstan && vendor/bin/pint --dirty
```

---

## 📁 Project structure (key areas)

```
app/
├── Http/Controllers/     # Auth, OAuth, notes, orgs, transactions, themes, payments
├── Mail/                 # Transactional mailables (OTP, invitations, password reset)
├── Models/               # User, Note, Organization, Payment, transactions, wallets…
├── Support/              # ThemeStyle (CSS-variable sanitizing)
bootstrap/app.php         # Middleware config (CSRF exception for the PayOS webhook)
config/services.php       # Google (Socialite), PayOS, ImageKit credentials
database/migrations/      # Schema (users, notes, orgs, wallets, transactions, payments…)
public/js/noteket.js      # Card drag/drop, share list, payment button + point→VND preview
resources/views/          # Blade views (layouts, notes, organizations, payment, themes…)
routes/web.php            # All web routes (94) + rate limiters (smart / authentication / transaction)
tests/Feature/            # Pest suite — 95 tests
report/                   # Audit & test reports (V1 → V7), Vietnamese
```

---

## 📄 License

MIT (Laravel-based project skeleton, extended for Noteket).
