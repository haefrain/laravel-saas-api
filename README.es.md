# SaaS Projects API — API REST Laravel grado producción

[![CI](https://github.com/haefrain/laravel-saas-api/actions/workflows/ci.yml/badge.svg)](https://github.com/haefrain/laravel-saas-api/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20)

🇬🇧 [English version](README.md)

Una API REST grado producción y multi-tenant para un SaaS de equipos / proyectos / tareas, construida para mostrar cómo se estructura un código Laravel senior: auth por token (Sanctum), roles y permisos por tenant (spatie), controladores delgados sobre una capa de servicios, policies, API resources, eventos en cola, versionado, rate limiting, documentación OpenAPI autogenerada — todo bajo análisis estático (Larastan), estilo de código (Pint) y una suite de tests con Pest, en verde en CI contra MySQL real.

> 🚧 **En construcción** — desarrollado hito a hito con tests, análisis estático y CI en verde.

## Stack

- **Laravel 13**, PHP 8.4 — solo API REST (sin UI Blade)
- **Auth:** Laravel Sanctum (tokens de API)
- **Roles y permisos:** spatie/laravel-permission con la feature de *teams* para multi-tenancy
- **Base de datos:** MySQL 8 · **Cola/Caché:** Redis
- **Entorno de desarrollo:** Laravel Sail (Docker)
- **Calidad:** Pest, Larastan (nivel 6), Laravel Pint, GitHub Actions

## Arranque rápido

```bash
cp .env.example .env
./vendor/bin/sail up -d        # MySQL 8 + Redis + PHP 8.5
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail test         # Pest
```

La API se sirve en `http://localhost:8081`.

## Roadmap

- [x] **L1** — Scaffold, Sail, tooling (Pint, Larastan, Pest), CI
- [ ] **L2** — Autenticación Sanctum + usuarios
- [ ] **L3** — Teams, multi-tenancy y roles/permisos spatie
- [ ] **L4** — Proyectos (CRUD, policies, resources, requests)
- [ ] **L5** — Tareas, asignación y estados, eventos en cola
- [ ] **L6** — Versionado de API, rate limiting, manejo de errores, docs Scribe
- [ ] **L7** — Imagen Docker de producción, docs de arquitectura y ADRs

## Licencia

[MIT](LICENSE) © Efraín Hernández
