# Repository Guidelines

## Project Structure & Module Organization

This repository contains the INSTAT Reading Room system. The Laravel application lives in `STAT-LMS/`; run commands there. Server-side code is in `STAT-LMS/app/` (models, policies, Filament, Livewire, services, and notifications). Routes are in `routes/`; database files are in `database/`; Blade, CSS, and JavaScript sources are in `resources/`. Public files belong in `public/`. Tests are split between `tests/Feature/` and `tests/Unit/`.

## Build, Test, and Development Commands

```bash
cd STAT-LMS
composer setup                 # install dependencies, initialize .env, migrate, build assets
composer dev                   # run Laravel, queue listener, logs, and Vite together
composer test                  # clear config and run the full PHPUnit suite
php artisan test --filter=Name # run a focused test or test class
./vendor/bin/pint              # format PHP to Laravel Pint conventions
npm run dev                    # start the Vite development server
npm run build                  # produce production frontend assets
```

## Coding Style & Naming Conventions

Follow `.editorconfig`: UTF-8, LF line endings, four-space indentation, final newlines, and no trailing whitespace (Markdown is exempt). Use Laravel conventions: PascalCase classes, singular models, descriptive `*Policy`, `*Service`, and `*Test` suffixes, and timestamped `snake_case` migrations. Keep controllers, policies, Filament resources, and views focused. Run Pint after PHP changes; keep Blade and Tailwind changes consistent with existing theme files.

## Testing Guidelines

Use PHPUnit. Place request, authorization, database, and UI behavior tests in `tests/Feature/`; put isolated behavior in `tests/Unit/`. Name files and test classes after the behavior, for example `MaterialAccessEventsTest.php`. The test configuration uses in-memory SQLite, synchronous queues, array cache, and array sessions, so tests must create their own state with factories or seeders. Add or update a regression test for every behavior change, especially role/access rules and protected material delivery.

## Commit & Pull Request Guidelines

Use concise conventional-style subjects visible in project history: `feat: add catalog filter`, `fix: correct access scope`, or `style/ui: improve theme contrast`. Keep each commit narrowly scoped. Pull requests should state the user-facing change, link the related issue when available, list verification commands, and include screenshots for visual Filament, Blade, or theme changes. Call out migrations, configuration changes, or security implications explicitly.

## Security & Configuration

Never commit `.env`, credentials, OAuth secrets, or private repository files. Preserve authorization checks and audit-log behavior when changing access workflows. Review storage, streamed PDF, and role-policy changes with particular care.

## Agent Delegation

The primary agent owns planning, orchestration, integration, and decisions. Use no more than two sub-agents for this repository task. Reserve GPT-5.4 for diagnosing and fixing a concrete failure; use GPT-5.4-mini for exploration and straightforward implementation work.
