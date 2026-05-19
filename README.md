# INSTAT-RR-SPRIS

Reading Room repository system for UP INSTAT.

This repository's primary application is in `STAT-LMS/`. Manage research materials, physical/digital copies, borrow/access workflows, and immutable audit logs through role-based admin and user panels.

## Project Structure

- `STAT-LMS/` — Laravel + Filament application (main project)

## Stack

- Laravel 12 + PHP 8.2
- Filament v5 (locked to `v5.1.3` in current project state)
- SQLite for dev/test
- Vite + TailwindCSS v4
- PHPUnit 11

## Two Panels

| Panel | Path     | Roles                                               |
| ----- | -------- | --------------------------------------------------- |
| Admin | `/admin` | `super_admin`, `committee`, `it`, `staff/custodian` |
| User  | `/app`   | `faculty`, `student`                                |

Panel providers:

- `STAT-LMS/app/Providers/Filament/AdminPanelProvider.php`
- `STAT-LMS/app/Providers/Filament/UserPanelProvider.php`

## Core Data Model

```text
RrMaterialParents (title, abstract, access_level:1-3, SDGs...)
  └── RrMaterials (is_digital, is_available, file_name)
        ├── MaterialAccessEvents (borrow/request lifecycle)
        └── RepositoryChangeLogs (immutable audit)
```

All core models use UUID primary keys and soft deletes.

Access levels:

- `1` = student
- `2` = faculty/staff
- `3` = committee/IT

## User Roles

| Role                   | Value             | Effective Access Level |
| ---------------------- | ----------------- | ---------------------- |
| Super Admin            | `super_admin`     | admin-level operations |
| Reading Room Committee | `committee`       | 3                      |
| IT Administrator       | `it`              | 3                      |
| Staff/Custodian        | `staff/custodian` | 2                      |
| Faculty Member         | `faculty`         | 2                      |
| Student User           | `student`         | 1                      |

## Authentication

Both panels support standard email/password login.

### Google OAuth / SSO

Google login is available via Laravel Socialite:

- Redirect: `GET /auth/google/redirect`
- Callback: `GET /auth/google/callback`
- Existing accounts are linked automatically by matching email address
- Soft-deleted accounts are detected and blocked at login
- New users created via SSO are redirected to `/app/onboarding` to complete their profile before accessing the app

> **Limitation:** Google OAuth requires a registered domain name. It does not work on the university's VM-hosted system (IP-only access) at this time.

### User Model Fields

Users have extended profile fields: `f_name`, `m_name`, `l_name`, `std_number` (unique), `google_id` (unique), `is_profile_complete`.

## Admin Panel Features

### Onboarding

- Role-specific welcome page at `/admin/admin-onboarding`
- Feature cards tailored per role: Super Admin, Committee/IT, Staff/Custodian

### User Management

- Full CRUD for user accounts at `/admin/users`
- Role assignment and account status (ban/unban) controls

### Repository Management

- RR Materials catalog CRUD (title, abstract, keywords, SDGs, material type, publication date, author, adviser)
- Per-copy tracking for digital and physical materials
- Availability status and access-level controls

### Access and Audit

- Material access request/borrow workflow (approve/reject + reason)
- Overdue tracking and approver assignment
- Immutable `RepositoryChangeLogs` entries for model mutations

### Dashboard (`/admin`)

- Tabs: General, Borrow Requests, Access Requests
- Pending requests widgets with inline approve/reject actions — 60s polling
- Charts:
  - **Visitor & Borrower trend** — daily/weekly/monthly/yearly filter
  - **Physical vs Digital** — material type distribution

### System Usage Analytics (`/admin/system-usage`)

- 3 tabs: Materials, Trend, Users — 120s polling
- Widgets: top materials by access, top active users, monthly usage trend, system-wide stats
- Export action opens `/admin/system-usage/export-preview` (date-range filterable; not in sidebar nav)

## User Panel Features

### Onboarding

- Role-specific welcome page at `/app/user-onboarding`
- Feature cards tailored for Faculty and Student roles

### Profile Completion (`/app/onboarding`)

- New SSO users are gated here until they complete first name, last name, and optional student number
- Enforced as a middleware-registered panel page; users cannot access any other page until complete

### Catalog (`/app/user/catalogs`)

- Role-filtered catalog browsing and search
- Filtering for type/format/date/SDG and availability controls
- Visibility logic includes materials the user can still access via approved or pending requests, even when general availability is constrained

### Material Detail

- Request digital access
- Request physical borrow
- Open digital viewer/stream when authorized

### My Requests (`/app/user/requests`)

- Tabbed interface: All, Pending, Approved, Closed — with badge counts per tab
- View own request history and current statuses
- Cancel pending requests
- Status-toast polling at 20s

### Profile

- Account profile management and password update

## Key Behaviors

- Access-level updates notify impacted users via `AccessLevelChanged`
- Request status transitions notify requesters via `RequestStatusChanged` for `approved`, `rejected`, and `revoked`
- Due-soon borrow reminders are emitted by `BorrowDueSoon`; overdue borrows emit `BorrowOverdue`
- `SendDueSoonOnLogin` listener fires all pending due-soon and overdue notifications on each login; session-based deduplication prevents repeats
- `access:expire-digital` artisan command automatically revokes expired digital access requests (`due_at` passed), restores material availability, and notifies users via `RequestStatusChanged`
- Digital access requests support a `due_at` expiry date managed by the scheduled command
- Account edits can trigger `AccountDetailsChanged`
- Banning a user revokes active access events
- Digital file replacement removes the old file from storage
- PDFs are served via `/materials/{id}/viewer` (browser viewer) and `/materials/{id}/stream` (authenticated stream) — both require valid approved access and apply security headers
- **Server-side PDF watermarking**: `PdfWatermarkService` embeds a QR code containing user identity and timestamp into every served PDF before delivery; `PdfNormalizationService` handles format compatibility pre-watermark; client-side fallback applies if server-side watermarking fails

## Setup and Commands

Run all project commands from `STAT-LMS/`.

```bash
cd STAT-LMS
```

| Task                  | Command                              | Notes                                                                                        |
| --------------------- | ------------------------------------ | -------------------------------------------------------------------------------------------- |
| Initial setup         | `composer setup`                     | Runs install, env bootstrap, key generate, migration (`--force`), npm install, build         |
| Start dev environment | `composer dev`                       | Runs server, queue listener, pail logs, Vite dev server, and local warmup curls concurrently |
| Run all tests         | `composer test`                      | Clears config then runs `php artisan test`                                                   |
| Run specific tests    | `php artisan test --filter=TestName` | Preferred filtered test run                                                                  |
| Lint/format           | `./vendor/bin/pint`                  | Laravel Pint                                                                                 |
| Frontend dev          | `npm run dev`                        | Vite dev server                                                                              |
| Build assets          | `npm run build`                      | Vite production build                                                                        |

## Testing Defaults

Testing uses in-memory SQLite via `STAT-LMS/phpunit.xml`:

- `DB_CONNECTION=sqlite`
- `DB_DATABASE=:memory:`
- `QUEUE_CONNECTION=sync`
- `CACHE_STORE=array`
- `SESSION_DRIVER=array`

## CI/CD

- `.github/workflows/security-audit.yml` — runs `composer audit` on every push and pull request to check for known vulnerabilities in PHP dependencies

## Notes

- Keep command and behavior documentation in this root `README.md` as the canonical source.
- Keep `STAT-LMS/README.md` concise to reduce duplication and drift.
