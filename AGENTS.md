# AGENTS.md

Agent instructions for this repository. Keep changes minimal, scoped, and consistent with existing CodeIgniter 4 patterns.

## Project Snapshot

- Stack: PHP 8.3, CodeIgniter 4, MySQL
- Purpose: ABDM bridge gateway with API endpoints, audit/request logging, hospital/user admin, and HMS credential management
- Entry points:
  - Public/API: public/index.php
  - Routes: app/Config/Routes.php

## Fast Start Commands

- Install deps: composer install
- App key: php spark key:generate
- Run migrations: php spark migrate
- Start local server: php spark serve
- Run tests (if any): composer test

Reference docs:
- Setup and usage: [README.md](README.md)
- Local quick start: [QUICKSTART.md](QUICKSTART.md)
- Production deploy: [DEPLOYMENT.md](DEPLOYMENT.md)
- HMS access module details: [HMS_ACCESS_MANAGEMENT.md](HMS_ACCESS_MANAGEMENT.md)

## Architecture Map

- API and bridge controller: app/Controllers/AbdmGateway.php
- Admin UI controller: app/Controllers/Admin.php
- Login and portal auth: app/Controllers/Auth.php
- Base JSON helpers: app/Controllers/BaseController.php
- Models: app/Models/*
- Migrations: app/Database/Migrations/*
- Views: app/Views/admin/* and app/Views/auth/*

## Routing and Auth Conventions

- Route definitions are centralized in app/Config/Routes.php.
- Admin routes use the auth filter (session-based): app/Filters/AuthFilter.php.
- API routes use Authorization headers validated in app/Controllers/AbdmGateway.php.
- Supported API auth schemes:
  - Bearer token (gateway token or hospital user API token)
  - Basic auth (hospital username/password)

## Data and Migration Conventions

- Use CodeIgniter Forge in migrations with both up() and down().
- Keep naming consistent with existing timestamp and key patterns:
  - created_at, updated_at, optional last_login_at
  - indexed lookup keys and unique identifiers where appropriate
- Keep model allowedFields explicit and minimal.

## Important Implementation Notes

- Default behavior is test mode unless overridden:
  - app/Config/AbdmGateway.php sets testMode from GATEWAY_TEST_MODE (default true)
- In test mode, external calls and some DB logging paths are bypassed/mocked.
- Hospital API tokens are stored hashed (sha256) for admin-created users.
  - Token lookup hashes incoming bearer token before DB compare.
- Be careful when touching user creation/auth flows:
  - Keep token generation/storage and validation logic aligned.
- ForceHTTPS and page cache are enabled as required filters in app/Config/Filters.php.
  - Avoid changing global filter behavior unless explicitly requested.

## Change Guidelines for Agents

- Prefer narrow edits in existing controllers/models over large refactors.
- Preserve current response shapes for API endpoints unless a change is explicitly requested.
- When adding routes, update app/Config/Routes.php and keep admin routes behind auth filter.
- For DB changes, add a new migration instead of editing old migrations.
- If adding dependencies, update composer.json and explain why.
- Validate changes with the smallest relevant command set (for example: composer test, php spark migrate:status, targeted curl).

## High-Value Files to Read First

- app/Config/Routes.php
- app/Config/AbdmGateway.php
- app/Controllers/AbdmGateway.php
- app/Controllers/Admin.php
- app/Controllers/Auth.php
- app/Models/AbdmHospitalUser.php
- app/Models/HmsCredential.php
