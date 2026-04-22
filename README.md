# INSTAT-RR-SPRIS

Reading Room repository system for UP INSTAT — manages research materials, physical/digital copy tracking, borrow/access requests, and audit logs.

## Tech Stack

- Laravel 12 + PHP 8.2
- Filament v5 (UI)
- SQLite (dev/test), in-memory for tests
- Vite + TailwindCSS v4
- PHPUnit 11

## Two Panels

| Panel | Path     | Roles                          |
| ----- | -------- | ------------------------------ |
| Admin | `/admin` | Committee, IT, Staff/Custodian |
| User  | `/app`   | Faculty, Student               |

## Core Data Model

```
RrMaterialParents  (title, abstract, keywords, SDGs, access_level, author, adviser)
    └── RrMaterials[]  (is_digital, is_available, file_name)
            ├── MaterialAccessEvents[]  (borrow/request events: status, due_at, approver)
            └── RepositoryChangeLogs[]  (audit trail)
```

All models use UUIDs + soft deletes. `access_level` on RrMaterialParents: 1=Student, 2=Faculty/Staff, 3=Committee/IT only.

## User Roles

| Role                   | Value             | Access Level |
| ---------------------- | ----------------- | ------------ |
| Reading Room Committee | `committee`       | 3            |
| IT Administrator       | `it`              | 3            |
| Staff/Custodian        | `staff/custodian` | 2            |
| Faculty Member         | `faculty`         | 2            |
| Student User           | `student`         | 1            |

## Admin Panel Features

### Repository Management

**RR Materials** — catalog CRUD (title, abstract, keywords, SDGs, material type, publication date, author, adviser); role-filtered by access level; soft deletes; material types: Book, Thesis, Journal, Dissertation, Others

**Material Copies** — per-copy tracking (digital/physical); availability status; digital file storage on local disk; access-level filtering

### Access & Audit

**Material Access Events** — borrow/request approval workflow; inline approve/reject with rejection reason; overdue tracking; approver assignment; no delete (immutable)

**Repository Change Logs** — read-only audit trail of all model changes; records editor, before/after values, timestamp

### Dashboard Widgets

- Stats: Borrowed count, Overdue count, Requests count, Visitor count (polling every 60s)
- Pending Digital Access Requests table — inline approve/reject
- Pending Borrow Requests table — inline approve/reject
- Charts: Physical vs Digital access over time (line chart); Visitors vs Borrowers over time (bar chart)

## User Panel Features

### Catalog Browser (`/app/user/catalogs`)

- Card-grid view of available materials filtered by user's access level
- Search by title, author, keywords
- Filters: material type, format (digital/physical), publication date range, SDG tags, availability toggle
- Pagination (15/page)

### Material Detail View

- Request Digital Copy — submits for staff approval (disabled if already active request or user is banned)
- Borrow Physical Copy — submits borrow request (disabled if already active borrow or user is banned)
- View Document — direct access once approved (Committee/IT bypass approval)

### My Requests (`/app/user/requests`)

- Lists all personal borrow/access requests with status, type, due date
- Cancel pending requests
- Real-time status polling every 20s

### Profile

- Change password

## Key Behaviors

- **Access level change** on a material → sends `AccessLevelChanged` notification to all users with active requests on its copies
- **Request approved/rejected** → sends `RequestStatusChanged` notification to the requester
- **Borrow due soon** → `BorrowDueSoon` notification triggered on login
- **Admin edits user account** → sends `AccountDetailsChanged` notification to affected user
- **Overdue auto-detection** — events are marked overdue on retrieval if `due_at` has passed
- **User banned** → all active access events are immediately revoked
- **Digital file update** → old file is deleted from disk automatically

## Setup & Commands

All commands run from `STAT-LMS/`:

```bash
composer setup        # initial setup
composer dev          # start server + queue + logs + Vite (concurrently)
composer test         # run full test suite
php artisan test --filter=TestName  # single test
./vendor/bin/pint     # code style (Laravel Pint)
npm run build         # build frontend assets
```
