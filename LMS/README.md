# LMS

This directory contains the native Laravel 12 and Filament 5 application for LMS. The shared demo runs as a conventional Laravel application on Render with Supabase PostgreSQL and private object storage.

## Requirements

For normal Laravel development:

- PHP 8.2 or newer with the extensions required by `composer.json`
- Composer 2
- Node.js and npm
- PostgreSQL, MySQL, or SQLite plus the cache, session, queue, and storage drivers selected for the environment

## Native Laravel Setup

Run commands from this directory:

```bash
cd LMS
cp .env.example .env
```

Configure the database, session, cache, queue, mail, and application URL in `.env` before running the setup command. For a lightweight local SQLite setup, create `database/database.sqlite` and use settings similar to:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/LMS/database/database.sqlite
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

Install, initialize, and build the application:

```bash
composer setup
```

Start Laravel, Vite, the queue listener, and logs together:

```bash
composer dev
```

The native panels are:

| Panel | Path | Roles |
| --- | --- | --- |
| Admin | `/admin` | Super Admin, Committee, IT, Staff/Custodian |
| User | `/app` | Faculty, Student |

Normal deployments keep `DEMO_MODE=false` and use the configured authentication, database, mail, cache, queue, and OAuth services. The Render shared demo sets `DEMO_MODE=true` and `DEMO_RUNTIME=server`; it retains native Filament authentication while disabling server-demo polling and other mutable production behavior configured in `render.yaml`.

## Render Shared Demo

The repository-root `render.yaml` deploys `Dockerfile.render` from this directory. The container runs nginx and PHP-FPM, initializes the shared demo through the canonical Laravel seeders, stores application state in Supabase PostgreSQL, and stores protected PDFs in private Supabase Storage.

Before deploying, provide the secret environment values declared with `sync: false` in `render.yaml`. Do not commit those values. The CI workflow in `../.github/workflows/shared-demo-ci.yml` verifies PHP and JavaScript dependencies, the Laravel suite, a fresh PostgreSQL bootstrap, formatting, production platform requirements, and the Render container.

## Testing and Formatting

```bash
composer test
php artisan test --filter=TestName
php artisan test tests/Feature/DemoModeTest.php
./vendor/bin/pint --test
npm run build
```

## Important Files

| Path | Purpose |
| --- | --- |
| `config/demo.php` | Shared-demo runtime, polling, storage, and lifecycle settings |
| `Dockerfile.render` | Render container build |
| `docker/render/` | nginx, PHP, and container startup configuration |
| `../render.yaml` | Render service and environment declaration |
| `app/Services/SharedDemoLifecycleService.php` | Canonical shared-demo bootstrap, health, and reset behavior |

Never commit `.env`, credentials, OAuth secrets, private uploads, generated artifacts, or dependency directories.
