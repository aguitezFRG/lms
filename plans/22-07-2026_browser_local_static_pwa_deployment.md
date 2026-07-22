# Browser-local static INSTAT demo deployment

## Governance and contracts

- The primary agent plans, orchestrates, and decides. Use no more than two sub-agents; reserve GPT-5.4 for debugging and GPT-5.4-mini for exploration or straightforward implementation.
- Define versioned `DemoSession`, seed-data, and `DemoSessionStore` contracts before parallel work. Fixed assets are seed JSON and hosted PDFs.

## Static client foundation

- Create a Vite, React, TypeScript PWA in `STAT-LMS/client/` with Cloudflare Pages configuration and SPA fallback.
- Convert Laravel seed data to versioned static catalog and demo-profile JSON, and expose seeded PDFs as read-only hosted assets.
- Precache only the app shell and seed metadata. Never cache PDFs; remove obsolete app caches.

## Browser session and portability

- Keep profile/role, mutable catalog data, requests, notifications, audit entries, preferences, and uploaded PDF blobs in IndexedDB.
- Enforce a 100 MiB total application budget, with at most five PDFs and 10 MiB per PDF. Validate PDF type and signature.
- Reject oversized uploads/imports atomically after projected-usage checks.
- Export/import versioned `.instat-session.zip` archives, validating schema/version, PDF limits, and total projected usage before confirmed full replacement.

## Local demo workflow

- Replace login/OAuth with a local profile and role selector.
- Implement catalog/detail, request/approval/borrowing, notification/audit, profile/preferences, upload, and backup/import screens through the store API.
- Keep all actions browser-local. Role switching demonstrates the workflow. Seeded PDFs work online only and clearly report unavailability offline.

## Integration and deployment

- Build Cloudflare Pages preview and production deployments without database, server sessions, queues, auth providers, or cloud storage.
- Verify persistence, offline shell, PDF non-caching, deep links, quotas, backup/import replacement, and responsive desktop/mobile behavior.
