# Architecture Notes

## Application

- Laravel 12 serves a Blade frontend and JSON API from one origin.
- The frontend uses vanilla JavaScript and Vite. It keeps the JWT in `sessionStorage` and sends it as a Bearer token on protected API requests.
- `php-open-source-saver/jwt-auth` supplies token issuance, validation, and revocation through Laravel's `api` guard.
- Eloquent models map `User`, `Task`, and `TaskAttachment` to the application tables. The comments table is present, but there is no comments API workflow yet.

## Data and files

- Laravel migrations define the schema. `database/schema.sql` is a MySQL DDL reference; `database/task_management_dump.sql` is a schema-and-sample-data import for an empty database.
- Attachments are stored on Laravel's private `local` disk under `storage/app/private/tasks/{task_id}` and downloaded through an authenticated controller.
- Upload validation limits files to 50 MB and an explicit extension allowlist before metadata is recorded.

## Current boundaries

- Task and attachment routes require JWT authentication. Role-based authorization and task ownership policies are not implemented.
- There are no comment endpoints, event broadcasting, WebSocket/SSE updates, thumbnail workers, or other queue jobs.
- The UI displays attachments uploaded during the current browser session because there is no attachment-list endpoint.
- The database queue driver is not usable until queue tables and job handlers are introduced.