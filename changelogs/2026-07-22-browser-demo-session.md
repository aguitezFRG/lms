# Browser Demo Session Changelog

Date: 2026-07-22

## Summary

- Replaced the previous React LMS imitation with a static PHP-WASM shell that runs the native Laravel, Filament, Livewire, Blade, Eloquent, and SQLite application in the browser.
- Added browser-demo configuration, deterministic seed data, browser-local SQLite and upload storage, profile-based demo identity selection, and native redirects to the appropriate user or admin panel.
- Added a profile chooser with per-card loading indicators, profile-switch confirmation, and a viewport-centered confirmation modal.
- Disabled credential, OAuth, logout, password-management, and password-encryption interfaces in demo mode while leaving normal production behavior unchanged.
- Fixed PHP-WASM initialization, stale service-worker activation, iframe startup overlays, PHP 8.4 configuration deprecations, profile session persistence, nested-shell navigation, and onboarding-card links escaping the internal `/__php` runtime.
- Fixed Livewire `419 Page Expired` responses by using a stable deployment release token instead of generating a new token on every PHP-CGI request.
- Improved page rendering with build-time Laravel configuration, route, event, Filament component, and icon caches; browser-runtime file caching; and browser-local SQLite tuning.
- Hid the Super Admin `View as` selector in the demo and cleared stale role-preview state when switching demo profiles.
- Added a canonical static build that emits `client/dist/` and `.vercel/output/static`, validates payload checksums, rejects secret-bearing environment files, and remains within Vercel Hobby artifact limits.
- Rewrote `STAT-LMS/README.md` with the current native Laravel setup, PHP-WASM demo build, local preview, static-host requirements, Vercel prebuilt deployment, testing commands, and runtime behavior.
- Purged reproducible client staging payloads, compiled views, logs, test/build caches, and an accidental SQLite file; the canonical demo build now cleans its temporary client payload files automatically.
- Added a repository-root Vercel deployment runbook covering prerequisites, canonical builds, local verification, project linking, preview and production prebuilt deployments, CI automation, and rollback.

## Verification

- Confirmed native Filament pages and Livewire actions run through PHP-WASM in Chromium.
- Confirmed Livewire update requests return HTTP 200 after the release-token correction.
- Confirmed onboarding cards remain inside the Laravel iframe without restarting the runtime.
- Confirmed the profile-switch modal is exactly centered and Super Admin no longer displays the `View as` selector.
- Focused demo and role-view tests passed with 25 tests and 113 assertions; the latest onboarding regression suite passed with 9 tests and 28 assertions.
- The final static artifact is approximately 98.17 MB across 85 files.
