# STAT-LMS

Laravel 12 + Filament v5 application powering INSTAT-RR-SPRIS. See the [project README](../README.md) for full documentation.

## Quick Start

```bash
composer setup   # install deps + migrate + seed
composer dev     # start all services (server, queue, logs, Vite)
```

## Testing

```bash
composer test                          # full suite (in-memory SQLite)
php artisan test --filter=TestName     # single test
```

## Panels

| Panel | URL | Roles |
|-------|-----|-------|
| Admin | `/admin` | Super Admin, Committee, IT, Staff/Custodian |
| User  | `/app`   | Faculty, Student |
