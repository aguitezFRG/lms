# INSTAT-RR-SPRIS

Reading Room repository system for UP INSTAT.

This repository’s primary application is in `STAT-LMS/`. Manage research materials, physical/digital copies, borrow/access workflows, and immutable audit logs through role-based admin and user panels.

## Project Structure

- `STAT-LMS/` — Laravel + Filament application (main project)

## Stack

- Laravel 12 + PHP 8.2
- Filament v5 (locked to `v5.1.3` in current project state)
- SQLite for dev/test
- Vite + TailwindCSS v4
- PHPUnit 11

## Two Panels

| Panel | Path | Roles |
|------|------|------|
| Admin | `/admin` | `super_admin`, `committee`, `it`, `staff/custodian` |
| User | `/app` | `faculty`, `student` |

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

| Role | Value | Effective Access Level |
|------|------|------|
| Super Admin | `super_admin` | admin-level operations |
| Reading Room Committee | `committee` | 3 |
| IT Administrator | `it` | 3 |
| Staff/Custodian | `staff/custodian` | 2 |
| Faculty Member | `faculty` | 2 |
| Student User | `student` | 1 |

## Admin Panel Features

### Repository Management

- RR Materials catalog CRUD (title, abstract, keywords, SDGs, material type, publication date, author, adviser)
- Per-copy tracking for digital and physical materials
- Availability status and access-level controls

### Access and Audit

- Material access request/borrow workflow (approve/reject + reason)
- Overdue tracking and approver assignment
- Immutable `RepositoryChangeLogs` entries for model mutations

### Dashboard

- Summary stats widgets
- Pending requests widgets with inline actions
- Trends/usage charts

## User Panel Features

### Catalog (`/app/catalogs`)

- Role-filtered catalog browsing and search
- Filtering for type/format/date/SDG and availability controls
- Visibility logic includes materials the user can still access via approved or pending requests, even when general availability is constrained

### Material Detail

- Request digital access
- Request physical borrow
- Open digital viewer/stream when authorized

### My Requests (`/app/requests`)

- View own request history and current statuses
- Cancel pending requests
- Status-toast polling at `5s`

### Profile

- Account profile management and password update

## Key Behaviors

- Access-level updates notify impacted users via `AccessLevelChanged`
- Request status transitions notify requesters via `RequestStatusChanged` for `approved`, `rejected`, and `revoked`
- Due-soon borrow reminders are emitted by `BorrowDueSoon`
- Account edits can trigger `AccountDetailsChanged`
- Banning a user revokes active access events
- Digital file replacement removes the old file from storage

## Setup and Commands

Run all project commands from `STAT-LMS/`.

```bash
cd STAT-LMS
```

| Task | Command | Notes |
|------|------|------|
| Initial setup | `composer setup` | Runs install, env bootstrap, key generate, migration (`--force`), npm install, build |
| Start dev environment | `composer dev` | Runs server, queue listener, pail logs, Vite dev server, and local warmup curls concurrently |
| Run all tests | `composer test` | Clears config then runs `php artisan test` |
| Run specific tests | `php artisan test --filter=TestName` | Preferred filtered test run |
| Lint/format | `./vendor/bin/pint` | Laravel Pint |
| Frontend dev | `npm run dev` | Vite dev server |
| Build assets | `npm run build` | Vite production build |

## Testing Defaults

Testing uses in-memory SQLite via `STAT-LMS/phpunit.xml`:
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=:memory:`
- `QUEUE_CONNECTION=sync`
- `CACHE_STORE=array`
- `SESSION_DRIVER=array`

## Notes

- Keep command and behavior documentation in this root `README.md` as the canonical source.
- Keep `STAT-LMS/README.md` concise to reduce duplication and drift.
