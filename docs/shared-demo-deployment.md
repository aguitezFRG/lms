# Render and Supabase Shared Demo Operator Checklist

This is the end-to-end setup guide for the persistent, multi-user demo. It
applies only to the `demo/render-supabase-vercel` branch. The pure Vercel
browser demo remains on `main`; do not merge this branch into `main` or repoint
the existing Vercel domains.

The intended staging architecture is:

- Laravel, PHP-FPM, and nginx in a Docker web service on Render Free,
  Singapore
- PostgreSQL and a private `lms-materials` Storage bucket on Supabase Free,
  Singapore
- `render-demo-lms-staging.cntest.uk` proxied and protected by Cloudflare
  Access
- a Supabase Edge Function invoking an idempotent reset every day at 03:00
  Asia/Manila

Complete the sections in order. Create staging first and promote only after
every acceptance check passes.

## 1. Confirm the repository gate

1. In GitHub, open the `demo/render-supabase-vercel` branch.
2. Confirm the `Shared Demo CI` workflow is green for the commit you will
   deploy.
3. Confirm no `Deploy to Vercel` run was created for this branch.
4. Do not deploy from a dirty local checkout. The provider source branch must
   be exactly `demo/render-supabase-vercel`.

The cloud workflow must pass Composer and both npm audits, the complete SQLite
test suite, a fresh PostgreSQL migration and seed, Pint, both frontend builds,
the browser-only demo build, production PHP platform checks, and the Render
container build.

## 2. Prepare secrets locally

Generate each secret once. Store the values in a password manager; never commit
them, paste them into an issue, or expose them as browser environment
variables.

From `STAT-LMS/`, generate the Laravel application key:

```bash
php artisan key:generate --show
```

Generate two independent random secrets:

```bash
openssl rand -hex 32
openssl rand -hex 32
```

Assign the first value to both `DEMO_RESET_HMAC_SECRET` on Render and
`RESET_HMAC_SECRET` in the Supabase Edge Function. Assign the second value to
`CRON_SHARED_SECRET` in the Edge Function and Supabase Vault. Keep the Laravel
`APP_KEY` stable across all staging redeployments; changing it invalidates
encrypted cookies and stored encrypted values.

## 3. Create the staging Supabase project

1. Create a new Supabase Free project in Singapore. Do not reuse a production
   project.
2. Record its project reference.
3. In **SQL Editor**, run:

   ```sql
   create schema if not exists lms;
   ```

4. Do not manually create Laravel tables. The Render start process runs
   Laravel migrations inside the `lms` schema.
5. Do not expose `lms` through the Supabase Data API. This demo does not use
   Supabase Auth or direct browser database access.
6. In **Connect**, copy the IPv4-compatible **Session Pooler** URI. Use the
   exact value Supabase displays. This becomes Render's `DB_URL`.
7. Keep TLS enabled. The application sets `DB_SSLMODE=require`.

Use a different Supabase project, password, and credentials when production is
created later.

## 4. Create private Supabase Storage

1. In **Storage**, create a bucket named `lms-materials`.
2. Keep the bucket private.
3. Open the project's S3 configuration and create server-side S3 access keys.
4. Copy the direct Storage S3 endpoint, region, access key ID, and secret key.
5. Do not put these keys in frontend code. Generated S3 keys bypass Storage
   RLS and therefore belong only in Render.

Map the displayed values as follows:

| Supabase value | Render variable |
| --- | --- |
| Direct S3 endpoint | `SUPABASE_S3_ENDPOINT` |
| S3 region | `SUPABASE_S3_REGION` |
| Bucket name | `SUPABASE_S3_BUCKET` |
| Access key ID | `SUPABASE_S3_ACCESS_KEY_ID` |
| Secret access key | `SUPABASE_S3_SECRET_ACCESS_KEY` |

Use the direct endpoint shaped like
`https://PROJECT_REF.storage.supabase.co/storage/v1/s3`, not a public bucket
URL. The committed configuration uses path-style S3 requests.

## 5. Create Cloudflare Access before the first Render start

The Laravel server validates the Cloudflare Access JWT itself, so the Access
audience and team domain must exist before Render starts.

1. In Cloudflare Zero Trust, create a **Self-hosted** Access application for
   `render-demo-lms-staging.cntest.uk`.
2. Add an **Allow** policy for the owner and explicit named testers. Use email
   OTP or the chosen identity provider and an eight-hour session.
3. Copy the application's audience tag. This becomes `CF_ACCESS_AUD`.
4. Record the team domain, such as `example.cloudflareaccess.com`, without
   `https://` or a path. This becomes `CF_ACCESS_TEAM_DOMAIN`.
5. Under **Access controls → Service credentials → Service Tokens**, create a
   dedicated token for the reset function.
6. Add a **Service Auth** policy to the same Access application that accepts
   that token.
7. Save its client ID and client secret for the Edge Function. Never place
   them in Render or the repository.

The Edge Function uses the standard `CF-Access-Client-Id` and
`CF-Access-Client-Secret` headers. The Laravel middleware still rejects
requests that do not carry a valid Access assertion. `/up` is intentionally a
minimal unauthenticated process probe; application and reset routes remain
protected.

## 6. Create the Render Blueprint service

1. In Render, create a new Blueprint from this GitHub repository.
2. Select the root `render.yaml`.
3. Confirm the generated service has:

   - service: `instat-lms-shared-demo`
   - branch: `demo/render-supabase-vercel`
   - runtime: Docker
   - plan: Free
   - region: Singapore
   - root directory: `STAT-LMS`
   - Dockerfile: `Dockerfile.render`
   - health path: `/up`

4. Supply every variable marked `sync: false` during initial Blueprint
   creation:

| Render variable | Value source |
| --- | --- |
| `APP_KEY` | Stable output from `php artisan key:generate --show` |
| `DB_URL` | Supabase Session Pooler URI |
| `SUPABASE_S3_ENDPOINT` | Supabase direct Storage S3 endpoint |
| `SUPABASE_S3_ACCESS_KEY_ID` | Supabase server-side S3 key |
| `SUPABASE_S3_SECRET_ACCESS_KEY` | Supabase server-side S3 secret |
| `CF_ACCESS_TEAM_DOMAIN` | Cloudflare Access team hostname |
| `CF_ACCESS_AUD` | Cloudflare Access application audience |
| `DEMO_RESET_HMAC_SECRET` | First locally generated random secret |

5. Confirm the Blueprint's non-secret values remain unchanged:

   - `DEMO_MODE=true`
   - `DEMO_RUNTIME=server`
   - `DEMO_MATERIAL_DISK=supabase`
   - `DB_CONNECTION=pgsql`
   - `DB_SCHEMA=lms`
   - `DB_SSLMODE=require`
   - `FILESYSTEM_DISK=supabase`
   - `CF_ACCESS_ENFORCED=true`

6. Start the deploy and watch the logs. A healthy start must validate required
   variables, run `php artisan migrate --force`, bootstrap canonical demo data,
   optimize Laravel, and start PHP-FPM plus nginx.
7. Do not run `migrate:fresh` on Render. Restarts and redeployments are
   intentionally non-destructive.

If the Blueprint already existed when a new `sync: false` variable was added,
set that variable manually in the Render service. Render only prompts for
`sync: false` values during initial Blueprint creation.

## 7. Attach the hostname and DNS

1. In the Render service, add
   `render-demo-lms-staging.cntest.uk` as a custom domain.
2. Copy the CNAME target Render provides.
3. In Cloudflare DNS, create the matching CNAME and enable the Cloudflare
   proxy.
4. Wait for Render's custom-domain verification and TLS certificate to become
   ready.
5. Confirm Render still has:

   ```text
   APP_URL=https://render-demo-lms-staging.cntest.uk
   ASSET_URL=https://render-demo-lms-staging.cntest.uk
   ```

6. Visit the hostname in a private browser. Cloudflare Access must challenge
   before the Laravel profile chooser appears.

Do not use the default `onrender.com` hostname as the public demo URL. The
Laravel Access middleware protects application routes there as defense in
depth, but the custom Cloudflare hostname is the supported entry point.

## 8. Deploy and configure the reset Edge Function

Install and authenticate the Supabase CLI if it is not already available, then
run from the repository root:

```bash
supabase link --project-ref YOUR_STAGING_PROJECT_REF
supabase functions deploy reset-shared-demo --no-verify-jwt
```

`--no-verify-jwt` is required because the function performs its own
constant-time check of `CRON_SHARED_SECRET`; the scheduled bearer value is not
a Supabase user JWT.

Set these Edge Function secrets:

```bash
supabase secrets set \
  CF_ACCESS_CLIENT_ID=YOUR_SERVICE_TOKEN_CLIENT_ID \
  CF_ACCESS_CLIENT_SECRET=YOUR_SERVICE_TOKEN_CLIENT_SECRET \
  CRON_SHARED_SECRET=YOUR_SECOND_RANDOM_SECRET \
  RENDER_RESET_URL=https://render-demo-lms-staging.cntest.uk/internal/shared-demo/reset \
  RESET_HMAC_SECRET=YOUR_FIRST_RANDOM_SECRET
```

The Edge Function and Render must use the same HMAC secret:

```text
Edge Function RESET_HMAC_SECRET = Render DEMO_RESET_HMAC_SECRET
```

The function calculates the Asia/Manila calendar date as the idempotency key,
signs it, authenticates through Cloudflare using the service token, and retries
transient Render cold-start failures.

## 9. Schedule the daily 03:00 Manila reset

Supabase Cron runs in UTC. `0 19 * * *` is 03:00 the following day in
Asia/Manila, which does not observe daylight saving time.

Enable Supabase Cron and the network request integration in the dashboard.
Store the function URL and cron bearer secret in Vault:

```sql
select vault.create_secret(
    'https://YOUR_PROJECT_REF.supabase.co/functions/v1/reset-shared-demo',
    'shared_demo_reset_function_url'
);

select vault.create_secret(
    'YOUR_CRON_SHARED_SECRET',
    'shared_demo_cron_shared_secret'
);
```

Create the schedule:

```sql
select cron.schedule(
    'reset-shared-demo-0300-manila',
    '0 19 * * *',
    $$
    select net.http_post(
        url := (
            select decrypted_secret
            from vault.decrypted_secrets
            where name = 'shared_demo_reset_function_url'
        ),
        headers := jsonb_build_object(
            'Content-Type', 'application/json',
            'Authorization', 'Bearer ' || (
                select decrypted_secret
                from vault.decrypted_secrets
                where name = 'shared_demo_cron_shared_secret'
            )
        ),
        body := '{}'::jsonb
    );
    $$
);
```

Confirm exactly one active job exists:

```sql
select jobid, jobname, schedule, active
from cron.job
where jobname = 'reset-shared-demo-0300-manila';
```

Do not create duplicate schedules. Review `cron.job_run_details` after the
first scheduled reset.

## 10. Run staging acceptance

Do not create production until all checks pass:

1. Open `/up`; it must return a successful response.
2. Open `/health/ready` through an authenticated Cloudflare Access session; it
   must report healthy database, runtime state, and material storage.
3. Use two private browser contexts and select different demo profiles.
4. Submit a request in one context and approve it in the other. Both contexts
   must see the persisted state.
5. Upload a small PDF. Confirm another authorized context can view it and an
   unauthorized role cannot stream it.
6. Restart the Render service and confirm the database record and PDF remain.
7. Redeploy the same commit and confirm they still remain.
8. Invoke the reset Edge Function manually with
   `Authorization: Bearer YOUR_CRON_SHARED_SECRET`.
9. Repeat the invocation on the same Manila calendar date. The second request
   must be safely idempotent.
10. Confirm canonical rows were restored, mutable records were removed,
    `uploads/` is empty, and `seed/` objects remain.
11. Confirm the Render logs contain no credentials, stack traces, repeated
    migrations, storage errors, or reset failures.
12. Confirm `Shared Demo CI` is green and all three dependency audits report
    zero advisories.

## 11. Operate and monitor

- Check Render deploy and runtime logs after every change.
- Check `/health/ready` after deploys and before demonstrations.
- Review Supabase database, Storage usage, and Cron history regularly.
- Rotate the Cloudflare service token, S3 keys, reset HMAC secret, and cron
  bearer if any value is exposed.
- After rotating `DEMO_RESET_HMAC_SECRET`, update
  `RESET_HMAC_SECRET` in the Edge Function in the same maintenance window.
- Watch the shared upload quota. The reset removes only `uploads/`; canonical
  `seed/` objects are preserved.
- Resolve every Composer or npm security advisory before deployment,
  regardless of which dependency introduced it. Do not suppress advisories to
  make CI green.

## 12. Promote to production

Create new production resources rather than repointing staging:

1. Create a separate Supabase project, bucket, S3 keys, database password,
   Vault secrets, and Cron job.
2. Create a separate Cloudflare Access application and service token for
   `render-demo-lms.cntest.uk`.
3. Create a separate Render service or Blueprint instance with production-only
   secrets.
4. Repeat every acceptance check against production.
5. Keep staging available for future migrations and dependency upgrades.

Never reuse staging secrets in production. Never merge this cloud deployment
branch into the pure Vercel `main` branch.

## Official provider references

- [Render Blueprint specification](https://render.com/docs/blueprint-spec)
- [Render Docker services](https://render.com/docs/docker)
- [Render health checks](https://render.com/docs/health-checks)
- [Supabase Storage S3 authentication](https://supabase.com/docs/guides/storage/s3/authentication)
- [Supabase Cron](https://supabase.com/docs/guides/cron)
- [Scheduling Supabase Edge Functions](https://supabase.com/docs/guides/functions/schedule-functions)
- [Cloudflare Access service tokens](https://developers.cloudflare.com/cloudflare-one/access-controls/service-credentials/service-tokens/)
