# STAT-LMS

Laravel 12 + Filament v5 application powering INSTAT-RR-SPRIS.

Detailed architecture, behaviors, and command documentation are maintained in the repository root README:
- `../README.md`

## Quick Start

```bash
cd STAT-LMS
composer setup
composer dev
```

## Testing

```bash
composer test
php artisan test --filter=TestName
```

## Panels

| Panel | URL | Roles |
|------|------|------|
| Admin | `/admin` | Super Admin, Committee, IT, Staff/Custodian |
| User | `/app` | Faculty, Student |

