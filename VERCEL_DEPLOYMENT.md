# Deploying the Browser Demo to Vercel

This deployment is a static PHP-WebAssembly demo. Vercel serves the runtime, Laravel payload, frontend assets, and seeded PDFs; PHP, Laravel, Filament, Livewire, and SQLite execute inside each visitor's browser. Do not configure a Vercel Function, external database, Blob store, Redis instance, OAuth provider, or mail service for this demo.

The recommended deployment path is to build locally or in CI, then upload the generated [Vercel Build Output](https://vercel.com/docs/cli/deploying-from-cli) with `vercel deploy --prebuilt`.

## 1. Install the Build Requirements

Install these tools on the machine that will build the deployment:

- PHP 8.4 with SQLite/PDO, mbstring, intl, DOM/XML, OpenSSL, GD, ZIP, and zlib support
- Composer 2
- Node.js and npm
- `zip`, `rg`, `sed`, `sha256sum`, and standard GNU file utilities
- The current [Vercel CLI](https://vercel.com/docs/cli/deploy)

From the repository root, install the project dependencies:

```bash
cd STAT-LMS
composer install --no-interaction --prefer-dist
npm ci
npm --prefix client ci
```

No `.env` file is packaged into the browser demo. Never add production credentials or secrets to the static artifact.

## 2. Run Pre-deployment Checks

Run the relevant application checks before building:

```bash
composer test
./vendor/bin/pint --test
npm run build
```

If Chromium Playwright is installed, run the browser proof after starting the preview server as described in step 4.

## 3. Build the Static Artifact

From `STAT-LMS/`, run:

```bash
composer demo:build
```

The build creates two equivalent outputs:

- `STAT-LMS/client/dist/` for any compatible static host;
- `STAT-LMS/.vercel/output/` for Vercel's prebuilt deployment flow.

The command also creates and seeds the demo SQLite database, installs production Composer dependencies in an isolated temporary directory, caches Laravel and Filament discovery data, packages the PHP 8.4 WASM runtime, writes `demo-runtime-manifest.json`, checks its payload size and checksum, scans for packaged environment secrets, and enforces the static artifact limits.

Do not manually edit files inside either generated output. Re-run the canonical build after every source change.

## 4. Preview the Exact Static Output

Start the static preview server:

```bash
npm --prefix client run preview -- --host 127.0.0.1 --port 4173
```

Open `http://127.0.0.1:4173` and verify:

1. the loading shell completes without a manifest or checksum error;
2. the profile chooser renders;
3. Student and Super Admin profiles open their respective native Filament panels;
4. a Livewire action returns HTTP 200;
5. a browser-local mutation survives a refresh;
6. onboarding cards remain under `/__php/...` and do not restart the runtime.

The automated Chromium proof can be run in another terminal:

```bash
python3 client/tests/browser-proof.py chromium
```

## 5. Link the Vercel Project

Authenticate and link from `STAT-LMS/`, because that is where `.vercel/output/` is generated:

```bash
vercel login
vercel link
```

Choose the appropriate Vercel team and either create a new project or link an existing one. Vercel documents that linking creates local project metadata under `.vercel/`; this directory is ignored by Git. See the official [CLI project deployment guide](https://vercel.com/docs/projects/deploy-from-cli).

No runtime environment variables are required for this static browser demo. If CI uses Vercel credentials, keep `VERCEL_TOKEN`, `VERCEL_ORG_ID`, and `VERCEL_PROJECT_ID` in the CI secret store, never in the repository.

## 6. Create a Preview Deployment

After a successful build, deploy the prebuilt output:

```bash
vercel deploy --prebuilt
```

Vercel prints the preview URL. Open it in a fresh browser profile and repeat the checks from step 4. In particular, verify that the service worker starts over HTTPS, WASM files use the correct MIME type, deep links load, and seeded PDFs are delivered with `Cache-Control: no-store`.

The generated `.vercel/output/config.json` already defines the filesystem routing, catch-all shell fallback, service-worker scope, WASM content type, immutable runtime caching, and no-store paths.

## 7. Deploy to Production

When the preview is accepted, deploy the same prebuilt output to production:

```bash
vercel deploy --prebuilt --prod
```

The `--prod` option assigns the deployment to the project's production domain. Vercel also supports staging a production deployment without immediately assigning its domain and promoting it later; see [Deploying Projects from the Vercel CLI](https://vercel.com/docs/cli/deploying-from-cli).

Configure or verify the custom domain in the Vercel dashboard after the first production deployment if one is required.

## 8. Verify the Production Deployment

Use a fresh browser profile and check the following:

- `demo-runtime-manifest.json` loads with `Cache-Control: no-store`;
- the manifest byte size and SHA-256 match `laravel-demo-v1.zip`;
- hashed files under `/assets/` and `/runtime/` use immutable caching;
- `.wasm` files are served as `application/wasm`;
- `/admin`, `/app`, and onboarding deep links open through the shell;
- Livewire POST requests under `/__php/livewire-*/update` return HTTP 200;
- profile switching, refresh persistence, and onboarding-card navigation work;
- no application records or local uploads are transmitted to Vercel.

## 9. Automate Future Deployments

For automatic Git deployments, use CI with native PHP 8.4, Composer, Node.js, and the Vercel CLI rather than depending on Vercel to assemble the Laravel payload. The CI job should:

1. check out the repository;
2. install PHP, Composer, and Node dependencies;
3. run the tests and formatting checks;
4. run `composer demo:build` inside `STAT-LMS/`;
5. run `vercel deploy --prebuilt` for preview branches;
6. run `vercel deploy --prebuilt --prod` only for the approved production branch.

Vercel notes that Git-connected pushes normally trigger builds and deployments, while prebuilt deployments upload an existing `.vercel/output` directory. See [Vercel Builds](https://vercel.com/docs/builds) and [Git deployments](https://vercel.com/docs/git).

## Rollback

Use the Vercel dashboard to promote a previously verified deployment, or rebuild and redeploy a known-good Git revision. Browser-owned demo data is independent of the static deployment, but a schema/runtime change may require the demo's reset or import flow when those contracts change.
