# API Reference

Local base URL: `http://127.0.0.1:8000/api`.

All routes except login require `Authorization: Bearer <access_token>` and accept JSON. Validation errors return `422`; missing or invalid authentication returns `401`.

## Authentication

### `POST /auth/login`

Request:

```json
{
  "email": "admin@gmail.com",
  "password": "admin123"
}
```

Success returns `access_token`, `token_type` (`Bearer`), `expires_in` (seconds), and `user`. Invalid credentials return `401`.

### `POST /auth/logout`

Revokes the presented JWT and returns a success message. The revoked token can no longer access protected routes.

### `GET /auth/me`

Returns the current authenticated user under `user`.

## Tasks

All task routes require JWT authentication. Task creation assigns `created_by` from the authenticated user; a client-supplied `created_by` value is ignored.

### `GET /tasks`

Returns Laravel's paginated JSON structure (`data`, `current_page`, `last_page`, `per_page`, `total`, and related pagination fields).

| Query parameter | Meaning |
| --- | --- |
| `page` | Page number |
| `per_page` | Page size from 1 to 100; default 15 |
| `status` | Exact status filter |
| `priority` | Exact priority filter |
| `assigned_user_id` | Filter by an existing user ID |
| `search` | Substring search in title or description |
| `sort_by` | `title`, `status`, `priority`, `due_date`, `created_at`, or `assigned_user_id` |
| `sort_direction` | `asc` or `desc`; default `desc` |

Example: `/api/tasks?status=pending&sort_by=due_date&sort_direction=asc&per_page=10`.

### `POST /tasks`

Required: `title` (string, maximum 255 characters). Optional: `description`, `status`, `priority`, `assigned_user_id` (must reference an existing user), and `due_date` (date). Returns `201` with the task under `data`.

### `PUT /tasks/{id}`

Accepts any subset of `title`, `description`, `status`, `priority`, `assigned_user_id`, and `due_date`. Returns the updated task under `data`.

### `DELETE /tasks/{id}`

Deletes the task and cascaded attachments/comments. Returns `204`.

## Attachments

All attachment routes require JWT authentication.

### `POST /tasks/{id}/attachments`

Send `multipart/form-data` with one `file` field. Maximum size is 50 MB. Allowed extensions: jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, ppt, pptx, csv, txt, mp4, and webm. Returns `201` with metadata and `download_url`; the private storage path is omitted.

### `GET /attachments/{id}/download`

Streams the file as an attachment. Returns `404` if metadata exists but the stored file is missing.

### `DELETE /attachments/{id}`

Deletes the stored file and metadata. Returns `204`.

There is no endpoint yet to list attachments for a task or manage task comments.