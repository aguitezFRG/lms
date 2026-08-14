# LMS shared demo

This branch contains the server-hosted LMS shared demo: a native Laravel, Filament, Livewire, and Blade application deployed to Render. It uses Supabase PostgreSQL for shared state and private Supabase Storage for protected material files. It is deliberately separate from the browser-local PHP-WASM/Vercel demo on `main`.

The Render service is defined in [render.yaml](render.yaml) and deploys the `demo/render-supabase-vercel` branch. Its public application URL is `https://lms-demo.cntest.uk`.

## Architecture

```text
Browser
  -> Cloudflare Turnstile (human verification)
  -> Render: nginx + PHP-FPM + Laravel/Filament/Livewire
       -> Supabase PostgreSQL (schema: lms)
       -> private Supabase Storage (protected PDFs)

Supabase Edge Function (scheduled externally)
  -> authenticated, signed Render reset endpoint
  -> canonical shared-demo reseed and upload cleanup
```

- `LMS/` is the Laravel 12 / Filament 5 application. Run application commands there.
- Render builds `LMS/Dockerfile.render`, runs migrations and `demo:bootstrap-shared`, then starts nginx and PHP-FPM.
- The shared demo requires `DEMO_MODE=true` and `DEMO_RUNTIME=server`; it uses normal server authentication rather than browser demo-profile routing.
- Shared state is PostgreSQL-backed. Local files on Render are ephemeral, so finalized materials belong in the private `lms-materials` Supabase bucket. Livewire temporary uploads stay local before final storage.
- The `reset-shared-demo` Supabase Edge Function invokes the signed `/internal/shared-demo/reset` endpoint with an idempotency key. Schedule it externally to reseed canonical data and remove shared uploads.

## Panels and roles

| Panel | Path | Roles |
| --- | --- | --- |
| Admin | `/admin` | Super Admin, LMS Committee, IT Administrator, Staff/Custodian |
| User | `/app` | Faculty Member, Student User |

The application manages research materials, physical/digital copies, access and borrowing workflows, notifications, and immutable repository audit logs. All core models use UUID primary keys and soft deletes.

```text
RrMaterialParents
  └── RrMaterials
        ├── MaterialAccessEvents
        └── RepositoryChangeLogs
```

Access levels are student (1), faculty/staff (2), committee/IT (3), and super-admin (4).

## Security and delivery

- Cloudflare Turnstile protects the server demo before application access. Siteverify validates success, action, and the exact configured hostname; a successful verification is stored for the configured session lifetime (two hours in Render).
- Google SSO uses Laravel Socialite at `/auth/google/redirect` and `/auth/google/callback`. Configure the exact public callback URL with the Google OAuth client; local development can use normal email/password authentication.
- PDF viewer and stream routes require an authenticated, authorized material-access event. PDFs are normalized and watermarked server-side before delivery, with a client-side fallback if that server operation fails.
- Optional Cloudflare Access enforcement is configured by environment variables. The health and reset endpoints remain available to their authenticated infrastructure callers.
- Never commit `.env`, database URLs, Supabase S3 credentials, Turnstile secrets, OAuth secrets, reset secrets, private uploads, or generated dependency directories.

## Local development

Requirements: PHP 8.2+ with the extensions required by `LMS/composer.json`, Composer 2, Node.js/npm, and a supported database. PostgreSQL is the closest match to the hosted environment; SQLite is suitable for the normal local and test workflow.

```bash
cd LMS
cp .env.example .env
# Configure APP_URL and the selected database, cache, session, queue, and mail drivers.
composer setup
composer dev
```

For an isolated SQLite setup, create `LMS/database/database.sqlite`, then set `DB_CONNECTION=sqlite` and `DB_DATABASE` to its absolute path. Do not put a production `DB_URL` in a local test environment: PHPUnit uses in-memory SQLite by default.

## Render and Supabase configuration

`render.yaml` declares non-secret production settings, including `DB_CONNECTION=pgsql`, schema `lms`, database-backed sessions/cache, synchronous queueing, `DEMO_MATERIAL_DISK=supabase`, and `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local`.

Set every `sync: false` variable in Render before deploying. In particular, the container refuses to start without application, database, demo-reset, OAuth, and Supabase Storage credentials. Configure these integration values consistently:

- `APP_URL` and `GOOGLE_REDIRECT_URI` must match the public hostname and Google OAuth callback.
- `TURNSTILE_HOSTNAME` must be the bare hostname `lms-demo.cntest.uk`; use a matching Turnstile site key and secret.
- Supabase Storage must use a private S3-compatible bucket named by `SUPABASE_S3_BUCKET`.
- The reset Edge Function needs its own cron secret, Cloudflare Access service credentials, Render reset URL, and the matching reset HMAC secret. Keep them in Supabase secrets, not this repository.

Render Free instances may sleep and have ephemeral local storage. The app therefore persists records and finalized uploads remotely; use the external reset function instead of relying on a Render one-off job or local scheduler.

## Commands and verification

Run these from `LMS/` unless noted otherwise.

| Task | Command |
| --- | --- |
| Install, initialize, migrate, and build | `composer setup` |
| Run Laravel, queue listener, logs, Vite, and local warmup | `composer dev` |
| Full SQLite regression suite | `composer test` |
| Focused test | `php artisan test --filter=TestName` |
| Shared-demo route/runtime regression | `php artisan test tests/Feature/DemoModeTest.php` |
| Laravel formatting check/fix | `./vendor/bin/pint --test` / `./vendor/bin/pint` |
| Production assets | `npm run build` |
| Fresh shared-demo bootstrap | `php artisan demo:bootstrap-shared --force` |
| Shared-demo health output | `php artisan demo:health-shared --json` |

The GitHub Actions workflow at `.github/workflows/shared-demo-ci.yml` runs dependency audits, Vite build, SQLite tests, a fresh PostgreSQL migration and canonical seed, Pint, production platform checks, and a Render Docker build/runtime verification for this branch.

## Repository map

| Path | Purpose |
| --- | --- |
| `LMS/config/demo.php` | Demo-runtime, storage, lifecycle, and upload-limit configuration |
| `LMS/Dockerfile.render` | Render production image |
| `LMS/docker/render/` | nginx, PHP, and container startup configuration |
| `LMS/app/Services/SharedDemoLifecycleService.php` | Atomic bootstrap, reset, cleanup, and health logic |
| `LMS/app/Http/Controllers/TurnstileController.php` | Human-verification flow |
| `LMS/app/Http/Controllers/SharedDemoResetController.php` | Signed reset entry point |
| `supabase/functions/reset-shared-demo/` | Scheduled reset caller |
| `render.yaml` | Render service and environment declaration |
| `LMS/README.md` | Concise Laravel-directory setup reference |

## Contributing

Follow [AGENTS.md](AGENTS.md). Keep Laravel code and tests in `LMS/`, add a focused regression test for behavior changes, run Pint after PHP edits, and preserve authorization and audit-log behavior. Do not apply browser-PHP-WASM/Vercel configuration to this server-runtime branch.
