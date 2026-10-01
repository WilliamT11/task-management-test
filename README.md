# Task Management Platform

Laravel 12 task-management API and Blade frontend for the full-stack assessment.

## Requirements

- PHP 8.2+, Composer, Node.js, and MySQL 8 or MariaDB.
- Create an empty MySQL database before running migrations.

## Setup

1. Install PHP dependencies and create the local environment file:

   ```powershell
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   ```

2. Set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` to match your MySQL server. Create the named database first. Use `CACHE_STORE=file`, `SESSION_DRIVER=file`, and `QUEUE_CONNECTION=sync` until database queue support is added.

3. Run migrations, seed sample records, and generate the JWT signing key:

   ```powershell
   php artisan migrate --seed
   php artisan jwt:secret
   ```

   The seeder can be rerun and creates five users, fifteen tasks, and ten comments. Local demo accounts are `admin@gmail.com` / `admin123` and `test@example.com` / `password`. Change them before any shared or deployed use.

4. Install/build frontend assets and start Laravel:

   ```powershell
   npm ci
   npm run build
   php artisan serve
   ```

   Open `http://127.0.0.1:8000`. For frontend hot reload, run `npm run dev` in a second terminal.

Choose either `php artisan migrate --seed` **or** import [`database/task_management_dump.sql`](database/task_management_dump.sql) into a new, empty database. Do not import the dump into an already migrated/seeded database.

## Tests

Run backend tests against isolated SQLite from PowerShell:

```powershell
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
php artisan test
Remove-Item Env:DB_CONNECTION
Remove-Item Env:DB_DATABASE
```

Build-check frontend assets with `npm run build`. Frontend automated tests are deferred.

## Implemented Features

- JWT login, logout, and current-user endpoints.
- Task list with pagination, filtering, sorting, and task create/update/delete.
- Private attachment upload/download/delete, limited to 50 MB and supported image, document, and video types.
- Blade/vanilla JavaScript dashboard for login, task management, filtering, and attachment drag-and-drop.

## Current Limitations

- The task-comments table and sample comments exist, but no comment API is implemented.
- Thumbnail generation, real-time updates, and queue jobs are not implemented. Do not use the database queue driver until its tables and jobs are added.
- The attachment UI lists uploads created in the current browser session; there is no endpoint to list existing task attachments.

See [`documentation/`](documentation/) for API, database, architecture, and deployment details.
