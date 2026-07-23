# Render and Supabase Shared Demo Runbook

This runbook applies only to the `demo/render-supabase-vercel` branch. The
pure Vercel browser demo remains on `main` and uses its existing deployment.

## 1. Create isolated provider resources

Create separate staging and production Supabase projects in Singapore. Do not
reuse a production database, bucket, S3 credential, or reset secret between
environments. In each project:

1. Create the `lms` PostgreSQL schema.
2. Create a private Storage bucket named `lms-materials`.
3. Create dedicated server-side S3 credentials for that bucket.
4. Copy the IPv4 Session Pooler connection string. Use port 5432 and require
   TLS.
5. Leave Supabase Auth and browser access to application tables disabled.

Run this once through the Supabase SQL editor:

```sql
create schema if not exists lms;
```

Laravel owns all tables inside that schema through its migrations. Do not
create application tables manually and do not expose the schema through the
Supabase Data API.

## 2. Create the Render service

Create the service from the repository Blueprint at `render.yaml`. Confirm:

- branch: `demo/render-supabase-vercel`
- region: Singapore
- plan: Free
- root directory: `STAT-LMS`
- health path: `/up`
- custom staging hostname: `render-demo-lms-staging.cntest.uk`

Set every `sync: false` variable in Render. `APP_KEY` must be generated once
with `php artisan key:generate --show` and then kept stable. `DB_URL` must be
the staging Supabase pooler URL. Never paste secrets into Blueprint YAML,
commits, logs, screenshots, or browser variables.

The container start command runs migrations and the non-destructive canonical
bootstrap. It never runs `migrate:fresh`. A restart therefore preserves shared
records unless the scheduled reset intentionally restores the seed.

## 3. Put Cloudflare Access in front

Create a self-hosted Cloudflare Access application for the custom hostname.
Start with an allow rule for the owner and add named testers. Configure an
eight-hour session and email OTP or the selected identity provider.

Copy the Access team domain and application audience into Render as
`CF_ACCESS_TEAM_DOMAIN` and `CF_ACCESS_AUD`. The team domain is a hostname such
as `example.cloudflareaccess.com`, without a scheme or path. Keep
`CF_ACCESS_ENFORCED=true`.

Create a separate Access service token for the reset function. Its client ID
and secret belong only in Supabase Edge Function secrets. Requests to the
default Render hostname cannot bypass the Laravel JWT check; `/up` remains a
minimal unauthenticated process probe.

## 4. Configure the reset function

Deploy `supabase/functions/reset-shared-demo` to the matching Supabase project.
Set these function secrets:

- `CF_ACCESS_CLIENT_ID`
- `CF_ACCESS_CLIENT_SECRET`
- `CRON_SHARED_SECRET`
- `RENDER_RESET_URL`
- `RESET_HMAC_SECRET`

`RESET_HMAC_SECRET` must exactly match Render's
`DEMO_RESET_HMAC_SECRET`. Set `RENDER_RESET_URL` to:

```text
https://render-demo-lms-staging.cntest.uk/internal/shared-demo/reset
```

Schedule an authenticated invocation for `0 19 * * *` UTC, which is 03:00 in
Asia/Manila. Send `Authorization: Bearer <CRON_SHARED_SECRET>` to the Edge
Function. The function supplies the Manila calendar date as the idempotency
key, traverses Cloudflare Access with its service token, and retries transient
Render cold-start failures.

## 5. Staging acceptance

Before creating production resources:

1. Confirm `/up` returns success and `/health/ready` is healthy through
   Cloudflare Access.
2. Select different demo profiles in two isolated browser contexts.
3. Submit and approve one request across those contexts.
4. Upload a PDF and confirm a second authorized context can view it.
5. Confirm an unauthorized role cannot stream the PDF.
6. Restart and redeploy Render; verify records and objects remain.
7. Invoke one manual reset, then repeat it with the same idempotency key.
8. Confirm canonical counts return, `uploads/` is empty, and `seed/` remains.
9. Confirm the dependency audits, full PHPUnit suite, both frontend builds,
   browser-demo build, PostgreSQL smoke, and container build pass in Shared
   Demo CI.

Promote by reproducing the same configuration with the production Supabase
project and `render-demo-lms.cntest.uk`. Do not repoint the Vercel domains and
do not merge the cloud branch into `main`.
