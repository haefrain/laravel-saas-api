# SaaS Projects API — Production-grade Laravel REST API

[![CI](https://github.com/haefrain/laravel-saas-api/actions/workflows/ci.yml/badge.svg)](https://github.com/haefrain/laravel-saas-api/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20)

🇪🇸 [Versión en español](README.es.md)

A production-grade, multi-tenant REST API for a team / projects / tasks SaaS, built to show how a senior Laravel codebase is structured: token auth (Sanctum), team-scoped roles & permissions (spatie), thin controllers over a service layer, policies, API resources, queued events, versioning, rate limiting, auto-generated OpenAPI docs — all under static analysis (Larastan), code style (Pint) and a Pest test suite, green in CI against real MySQL.

> 🚧 **Work in progress** — built milestone by milestone with green tests, static analysis and CI.

## Stack

- **Laravel 13**, PHP 8.4 — REST API only (no Blade UI)
- **Auth:** Laravel Sanctum (API tokens)
- **Roles & permissions:** spatie/laravel-permission with the *teams* feature for multi-tenancy
- **Database:** MySQL 8 · **Queue/Cache:** Redis
- **Dev environment:** Laravel Sail (Docker)
- **Quality:** Pest, Larastan (level 6), Laravel Pint, GitHub Actions

## Quick start

```bash
cp .env.example .env
./vendor/bin/sail up -d        # MySQL 8 + Redis + PHP 8.5
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail test         # Pest
```

The API is served at `http://localhost:8081`.

## Roadmap

- [x] **L1** — Scaffold, Sail, tooling (Pint, Larastan, Pest), CI
- [ ] **L2** — Sanctum authentication + users
- [ ] **L3** — Teams, multi-tenancy & spatie roles/permissions
- [ ] **L4** — Projects (CRUD, policies, resources, requests)
- [ ] **L5** — Tasks, assignment & status, queued events
- [ ] **L6** — API versioning, rate limiting, error handling, Scribe docs
- [ ] **L7** — Production Docker image, architecture docs & ADRs

## License

[MIT](LICENSE) © Efraín Hernández
