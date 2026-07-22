# INSTAT Reading Room System

This directory contains the native Laravel 12 and Filament 5 application for INSTAT-RR-SPRIS. It can run as a conventional Laravel deployment or be packaged as a static browser demo that executes PHP 8.4, Laravel, Filament, Livewire, and SQLite locally through PHP-WebAssembly.

## Requirements

For normal Laravel development:

- PHP 8.2 or newer with the extensions required by `composer.json`
- Composer 2
- Node.js and npm
- MySQL and Redis for the production-style defaults, or SQLite and local drivers for development

The browser-demo build additionally requires PHP 8.4-compatible dependencies, `zip`, `rg`, `sed`, `sha256sum`, and enough disk space for the isolated Composer staging directory.

## Native Laravel Setup

Run commands from this directory:

```bash
cd STAT-LMS
cp .env.example .env
```

Configure the database, session, cache, queue, mail, and application URL in `.env` before running the setup command. For a lightweight local SQLite setup, create `database/database.sqlite` and use settings similar to:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/STAT-LMS/database/database.sqlite
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

Normal deployments keep `DEMO_MODE=false` and use the configured authentication, database, mail, cache, queue, and OAuth services.

## Static Browser Demo

The browser demo is not a React reimplementation. The shell in `client/` starts the repository's real Laravel application inside a service worker and displays its responses in an iframe. Demo records, sessions, and uploaded files stay in the visitor's browser; Vercel or another static host only serves the packaged assets.

Install both sets of JavaScript dependencies before the first demo build:

```bash
npm ci
npm --prefix client ci
```

Build the canonical static artifact:

```bash
composer demo:build
```

This command:

1. builds the Laravel Vite assets;
2. creates and seeds a deterministic demo SQLite database;
3. installs production Composer dependencies in an isolated staging directory;
4. caches Laravel configuration, routes, events, Filament components, and icons;
5. packages Laravel and the pinned PHP 8.4 WASM runtime;
6. verifies the runtime manifest, artifact limits, and absence of packaged environment secrets;
7. writes `client/dist/` and the equivalent `.vercel/output/` prebuilt deployment.

Preview the result locally:

```bash
npm --prefix client run preview -- --host 127.0.0.1 --port 4173
```

Then open `http://127.0.0.1:4173`. A fresh browser profile starts from deterministic seed data. The chooser provides Student, Faculty, Staff/Custodian, Committee, IT, and Super Admin demo identities without passwords.

### Browser-Demo Behavior

- `DEMO_MODE=true` is applied inside the packaged runtime only.
- SQLite data is stored at `/persist/database/demo.sqlite` through browser-owned storage.
- Local private uploads use `/persist/storage/app/private`.
- Sessions use browser cookies, cache data stays in the runtime filesystem, and queues run synchronously.
- Mail, Redis, OAuth, credential management, and daemon workers are not used.
- Seeded PDFs are static network assets; local PDFs remain browser-owned.
- Current desktop Chrome, Edge, Firefox, and Safari are the support target. Mobile browsers are best-effort.
- Startup is online-only and requires HTTPS outside localhost because the runtime depends on service workers and WebAssembly.

### Static Hosting Requirements

A non-Vercel host must provide:

- HTTPS and root-scoped service workers;
- `application/wasm` for WASM assets;
- immutable caching for hashed runtime assets;
- `no-store` for `demo-runtime-manifest.json` and seeded PDFs;
- filesystem-first routing followed by a catch-all rewrite to `index.html`.

The repository-root `vercel.json` supplies these rules for standard Vercel builds. The generated `.vercel/output/config.json` supplies equivalent rules for prebuilt deployment:

```bash
vercel deploy --prebuilt
```

Run the prebuilt command from `STAT-LMS/`, where `.vercel/output/` is generated.

## Testing and Formatting

```bash
composer test
php artisan test --filter=TestName
php artisan test tests/Feature/DemoModeTest.php
./vendor/bin/pint --test
npm run build
```

With the static preview running on port 4173, the Chromium browser proof can be run separately:

```bash
python3 client/tests/browser-proof.py chromium
```

## Important Files

| Path | Purpose |
| --- | --- |
| `config/demo.php` | Browser-demo flags, paths, quotas, and archive contract |
| `resources/views/demo/profiles.blade.php` | Native Blade demo-profile chooser |
| `client/src/runtime-worker.js` | PHP-WASM, SQLite, filesystem, and service-worker runtime |
| `client/src/main.js` | Outer startup shell and iframe bootstrap |
| `scripts/build-browser-demo.sh` | Canonical static build |
| `client/dist/demo-runtime-manifest.json` | Runtime/payload integrity contract generated by the build |
| `../vercel.json` | Vercel static build, headers, and rewrite configuration |

Never commit `.env`, credentials, OAuth secrets, private uploads, generated payloads, dependency directories, or browser-local data.
