# ExclusionZone

ExclusionZone is a text-based post-nuclear multiplayer RPG prototype built with Laravel 13, Livewire 4, Flux UI, Tailwind CSS, and Alpine.js. The MVP focuses on a playable city-driven survival loop: travel, perform city actions, gain skill XP, collect loot, and progress your survivor.

## Introduction

This repository implements the MVP described in:

- `docs/spec.md`
- `docs/implementation-steps.md`
- `docs/testPlan.md`

Current MVP highlights:

- Role/task access model (`admin`, `moderator`, `premium`, `user`, `guest`)
- Seeded world with 6 countries and 12 cities
- City-specific action menus driven by player location
- Skill progression + inventory rewards from city actions
- Premium cosmetics that are cosmetic-only (no gameplay advantage)
- Moderator mute flow and admin role-change flow with audit logging

## Prerequisites

- PHP 8.3+
- Composer 2+
- Node.js 20+ and npm
- SQLite (default local setup)

## Installation

1. Clone the repository.
2. Install dependencies and bootstrap the app:

```bash
composer setup
```

`composer setup` runs:

- `composer install`
- `.env` creation from `.env.example` (if missing)
- `php artisan key:generate`
- `php artisan migrate --force`
- `npm install`
- `npm run build`

If you prefer manual setup:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

## Usage

### Run the app locally

```bash
composer run dev
```

This starts Laravel, queue worker, and Vite concurrently.

### MVP flow

1. Open the home page and register/login.
2. Enter the **City Menu** dashboard.
3. Travel between connected cities.
4. Perform city actions to gain XP and loot.
5. Equip premium cosmetics (premium users) without stat/resource bonuses.
6. Use moderation/admin tools when authenticated with appropriate roles.

## Testing and Validation

Run lint + test suite:

```bash
composer test
```

Run formatting checks only:

```bash
composer lint:check
```

Build frontend assets:

```bash
npm run build
```

## Bug / Issue Reporting

Please report bugs and feature requests using GitHub Issues:

- https://github.com/Dregozone/ExclusionZone/issues

When reporting, include:

- Steps to reproduce
- Expected vs actual behavior
- Environment details (PHP/Node versions, OS)
- Relevant logs or screenshots

## Documentation

Project planning and implementation documents:

- `docs/spec.md`
- `docs/implementation-steps.md`
- `docs/testPlan.md`

## License

MIT (as declared in `composer.json`).
