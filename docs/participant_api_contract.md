# ElevateHer360 Participant API Contract (v1)

Base URL: `https://site.elevateher360.org/api/v1/participant`
All paths below are relative to that base (e.g. `GET /lessons/12` = `GET https://site.elevateher360.org/api/v1/participant/lessons/12`).

Source of truth: `routes/api.php`, `app/Http/Controllers/Api/V1/Participant/*`, `app/Services/Participant/ParticipantLessonService.php`, `bootstrap/app.php`.
Tests: `tests/Feature/Participant/ParticipantLessonApiTest.php`.

## Conventions

### Auth
- `POST /login` is public (throttled 10/min). Every other endpoint requires `Authorization: Bearer <token>` (Sanctum personal access token).
- The token's user must be `user_type = participant` and `status = active`, otherwise **403**.
- Send `Accept: application/json` on JSON calls. File downloads may send `Accept: */*`; errors are still JSON.
- Only attach the Bearer token to requests for the API host. Every download URL the API returns is on the API host.

### Success responses
The API has no generic `{data: ...}` wrapper, and existing clients (the Flutter app, the web PWA `public/pwa.js`) depend on that. Responses use these shapes:
- **Single resource**: a named top-level key, e.g. `{"course": {...}}`, `{"lesson": {...}}`, `{"user": {...}}`.
- **Lists**: the standard Laravel paginator, with items in `data`:
  `{"current_page":1,"data":[...],"first_page_url":"...","from":1,"last_page":3,"last_page_url":"...","links":[...],"next_page_url":"...","path":"...","per_page":20,"prev_page_url":null,"to":20,"total":55}`
- **Actions**: `{"message": "Human readable text.", ...extra fields}`.
- Dates are ISO-8601 strings. Laravel model timestamps look like `2026-09-24T13:13:20.000000Z`; fields built by hand look like `2026-09-24T13:13:20+00:00`.

### Error responses (every `api/*` route)
Errors are always JSON with a readable `message`, never HTML:

| Status | When | Body |
|---|---|---|
| 401 | missing/expired/invalid token | `{"message":"Unauthenticated."}` |
| 403 | not a participant, not enrolled, module locked | `{"message":"You are not enrolled in the course for this lesson."}` |
| 404 | record doesn't exist / unpublished / no file | `{"message":"Lesson not found."}` · `{"message":"This lesson has no downloadable file."}` |
| 404 | unknown path | `{"message":"The requested API endpoint does not exist."}` |
| 405 | wrong HTTP method | `{"message":"The GET method is not supported for route ..."}` |
| 422 | validation | `{"message":"The completed field is required.","errors":{"completed":["The completed field is required."]}}` |
| 422 | business rule | `{"message":"Maximum attempts reached."}` |
| 429 | throttled | `{"message":"Too Many Attempts."}` |
| 500 | unexpected (production) | `{"message":"Something went wrong on the server. Please try again later."}` |

Clients should show `message` to the user. For 422, `errors` maps each field to a list of messages.
If a route-model-bound record is missing, the message is `"<Model> not found."` (for example `Lesson not found.`, `Course not found.`, `Assessment not found.`, `Learning File not found.`).

---

## Authentication

### POST /login
Public. Body (JSON): `email` (required), `password` (required), `device_name` (optional, ≤120).

200:
```json
{"token":"12|abc...","token_type":"Bearer","user":{"id":5,"name":"Jane","email":"jane@x.org","phone":"0700..."}}
```
422 (bad credentials or not a participant account):
```json
{"message":"Invalid email or password.","errors":{"email":["Invalid email or password."]}}
```

### POST /logout
Revokes the current token. 200 `{"message":"Signed out successfully."}`

### GET /me
200 `{"user":{"id":5,"name":"Jane","email":"...","user_type":"participant","status":"active","profile":{...}|null, ...}}`

### GET /dashboard
200:
```json
{
  "summary":{"courses":2,"in_progress":1,"completed":0,"pending_assignments":3,"unread_notifications":4},
  "courses":[{"id":1,"title":"Digital Marketing","status":"in_progress","progress_percent":50.0}],
  "next_mentorship_session":{...}|null,
  "last_synced_at":"2026-09-30T10:00:00+00:00"
}
```

---

## Courses and lessons

### GET /courses
Lists the courses the participant is enrolled in. Query: `page`, `search` (title/code, optional), `updated_since` (datetime, optional).
200: paginator of courses. Each item includes the course columns, `cohorts`, and `modules_count`.

### GET /courses/{course}
403 if not enrolled; 404 if the course doesn't exist or is soft-deleted.
Returns only published modules and lessons, ordered by `position`.

200:
```json
{
  "course": {
    "id": 1, "title": "Digital Marketing", "code": null, "summary": "...", "description": "...",
    "status": "published", "cohorts": [],
    "enrolment": {"status": "in_progress", "progress_percent": 50.0},
    "modules": [
      {
        "id": 1, "course_id": 1, "title": "Module 1", "description": null, "position": 1, "is_published": true,
        "is_locked": false,
        "lessons": [ <Lesson object, see below>, ... ]
      }
    ],
    "assessments": [ <Assessment object, see below>, ... ],
    "announcements": [ ... ]
  }
}
```

#### Lesson object
Used by `GET /courses/{course}` (inside `modules[].lessons[]`) and `GET /lessons/{lesson}` (under `lesson`).

```json
{
  "id": 1,
  "course_module_id": 1,
  "module_id": 1,
  "course_id": 1,
  "title": "Lesson 1",
  "content_type": "file",
  "type": "file",
  "content": "lesson",
  "body": "lesson",
  "video_url": null,
  "external_url": null,
  "estimated_minutes": 30,
  "duration_minutes": 30,
  "position": 1,
  "is_published": true,
  "is_locked": false,
  "has_file": true,
  "file_name": "lesson-1.pdf",
  "file_mime_type": "application/pdf",
  "file_size_bytes": 12288,
  "resource_url": "https://site.elevateher360.org/api/v1/participant/lessons/1/download",
  "file_url":     "https://site.elevateher360.org/api/v1/participant/lessons/1/download",
  "download_url": "https://site.elevateher360.org/api/v1/participant/lessons/1/download",
  "download_path": "/lessons/1/download",
  "files": [
    {"id": 7, "name": "Handout.docx", "mime_type": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
     "size_bytes": 20480, "download_path": "/lessons/1/files/7/download",
     "download_url": "https://site.elevateher360.org/api/v1/participant/lessons/1/files/7/download"}
  ],
  "progress": {"completed": false, "completed_at": null, "first_opened_at": null, "last_opened_at": null, "time_spent_seconds": 0},
  "created_at": "2026-09-24T13:13:20+00:00",
  "updated_at": "2026-09-24T13:13:20+00:00"
}
```
Field notes:
- `content_type` / `type`: one of `text`, `video`, `file`, `link`, `mixed`.
- `content` / `body`: the text or HTML lesson body. It may contain HTML written in the admin editor. It is included in both the course payload and the lesson detail.
- `resource_url` / `file_url` / `download_url`: `null` unless a file really exists on the server. When set, it points at the **authenticated** download endpoint (Bearer token required). It is never a public `/storage/...` URL. `download_path` is the same thing relative to the participant base URL, and is the recommended way to call it through Dio.
- `files`: extra learning files an admin attached to the lesson. If the lesson has no `file_path`, the first attached file becomes the primary download.
- `external_url`: an external link. If an admin typed a full `http(s)://` URL as the lesson's file path, it appears here and `resource_url` is `null`.
- `is_locked`: `true` when sequential modules or instructor release apply and this module isn't unlocked yet. `/lessons/*` calls then return 403.
- A `file` lesson with no uploaded file has `has_file: false` and `resource_url: null`. Hide the download option in that case.

### GET /lessons/{lesson}
Lesson detail. It also sets `first_opened_at` / `last_opened_at` in the participant's progress.
- 404 `{"message":"Lesson not found."}`: the lesson doesn't exist, or the lesson or its module is unpublished.
- 403 `{"message":"You are not enrolled in the course for this lesson."}`
- 403 `{"message":"This module is locked. Complete the previous module or wait for your instructor to release it."}`

200: `{"lesson": <Lesson object>}` (in this response `is_locked` is `false`)

### GET /lessons/{lesson}/download
Streams the lesson's primary file. The same enrolment, publish and lock checks as `GET /lessons/{lesson}` apply.
Query: `inline=1` (optional) switches `Content-Disposition` from `attachment` to `inline`, for in-app viewers or players.
200 headers: `Content-Type: <file mime>`, `Content-Disposition: attachment; filename=lesson-1.pdf`, `Accept-Ranges: bytes` (Range requests are supported, so video can stream), `Cache-Control: private, max-age=0, must-revalidate`.
404 `{"message":"This lesson has no downloadable file."}`, 403 or 401 as above.

### GET /lessons/{lesson}/files/{file}/download
Streams one attached learning file (`files[].id`). The checks are the same as above. 404 `{"message":"File not found for this lesson."}` if the file belongs to a different lesson. Supports `inline=1`.

### PUT /lessons/{lesson}/progress  (POST also accepted)
Same access checks as lesson detail. Body (JSON): `completed` (boolean, required), `time_spent_seconds` (integer ≥0, optional, added to the stored total).
200:
```json
{"message":"Progress saved.","lesson_id":1,"completed":true,"completed_at":"2026-09-30T10:00:00+00:00",
 "time_spent_seconds":90,"course_id":1,"course_progress_percent":50,"course_status":"in_progress"}
```
When every published lesson is complete, `course_status` becomes `completed`.
422 `{"message":"The completed field is required.","errors":{"completed":["The completed field is required."]}}`

---

## Assignments / assessments

### GET /assignments
Published assessments for the participant's courses. Query: `page`, `type` (`assignment|quiz|exam`), `updated_since`. Returns a paginator of Assessment objects.

#### Assessment object
Contains all assessment columns plus these fields:
```json
{"id":3,"course_id":1,"title":"Brief","type":"assignment","instructions":"...","max_attempts":1,"due_at":"...","is_published":true,
 "course":{"id":1,"title":"Digital Marketing"},
 "attachment_path":"courses/1/assessments/x.pdf",
 "attachment_url":"https://site.elevateher360.org/api/v1/participant/assignments/3/attachment",
 "attachment_download_path":"/assignments/3/attachment",
 "attachment_name":"x.pdf"}
```
`attachment_url`, `attachment_download_path` and `attachment_name` are `null` when there is no attachment on disk. Use the authenticated endpoint below; don't build `/storage/...` URLs.

### GET /assignments/{assessment}/attachment
Streams the instructor attachment. 403 if not enrolled; 404 if the assessment is unpublished or has no file. Supports `inline=1`.

### POST /assignments/{assessment}/submit
`multipart/form-data`: `submission_text` (optional), `submission_file` (optional, ≤50MB, pdf/doc/docx/ppt/pptx/xls/xlsx/txt/zip/jpg/jpeg/png/webp), `client_submission_id` (optional idempotency key, ≤190).
201 `{"message":"Submission received.","attempt":{...}}`
200 (the same `client_submission_id` was already received) `{"message":"Submission already received.","attempt":{...},"duplicate":true}`
422 `{"message":"Maximum attempts reached."}` · 403 not enrolled · 404 `{"message":"Assessment not found."}`

---

## Mentorship, jobs, events, announcements, notifications

| Method | Path | Query/body | 200 response |
|---|---|---|---|
| GET | `/mentorship` | — | `{"matches":[...],"sessions":[...],"goals":[...]}` |
| GET | `/jobs` | `page`, `search`, `updated_since` | paginator; each item has `is_saved` (0/1) and `company_name` |
| POST | `/jobs/{job}/save` | — | `{"message":"Job saved."}` · 404 `{"message":"Job not found or no longer open."}` |
| DELETE | `/jobs/{job}/save` | — | `{"message":"Job removed from saved jobs."}` |
| GET | `/events` | `page`, `updated_since` | paginator |
| GET | `/announcements` | `page`, `updated_since` | paginator (each item has `course`) |
| GET | `/notifications` | `page`, `unread_only` (bool), `updated_since` | paginator (30 per page) |
| PUT | `/notifications/{notification}/read` | — | `{"message":"Notification marked as read."}` · 404 `{"message":"Notification not found."}` |

---

## Offline sync

### GET /sync
Query: `last_synced_at` (ISO datetime, optional). `since` is accepted as an alias. Leave both out for a full sync.
422 if the value isn't a date.
200:
```json
{"server_time":"...","last_synced_at":"...",
 "enrolments":[...],"courses":[...],"assignments":[<Assessment object>...],"announcements":[...],
 "mentorship":[...],"jobs":[...],"events":[...],"notifications":[...],"lesson_progress":[...],
 "local_reminders":[{"type":"assignment_deadline|mentorship_session|event","source_id":3,"title":"...","scheduled_at":"..."}]}
```
Store `last_synced_at` and send it on the next call. `courses` here have no modules or lessons; call `GET /courses/{id}` for the lesson tree.

### POST /offline-actions
Body (JSON): `{"operations":[{"client_operation_id":"uuid","type":"lesson_progress|save_job|unsave_job|notification_read","payload":{...}}]}` (up to 100 operations).
`actions` is accepted as an alias of `operations`, and `client_action_id` as an alias of `client_operation_id`.
Payloads: `lesson_progress` → `{lesson_id, completed, time_spent_seconds}` · `save_job` / `unsave_job` → `{job_id}` · `notification_read` → `{notification_id}`.
200:
```json
{"results":[
  {"client_operation_id":"a1","status":"processed","result":{"message":"Progress saved.","course_progress_percent":50, ...}},
  {"client_operation_id":"a2","status":"duplicate","result":{...}},
  {"client_operation_id":"a3","status":"failed","code":404,"message":"The item referenced by this operation no longer exists."}
 ],
 "server_time":"..."}
```
Remove `processed` and `duplicate` operations from the local queue. A `failed` operation with `code` 403 or 404 will never succeed, so drop it. Retry only on 500.

---

## Push notifications

### POST /device-token
Body: `device_id` (required), `token` (required; `fcm_token` is accepted as an alias), `platform` (`android|ios|web`), `app_version` (optional).
200 `{"message":"Device registered.","device":{...}}`

### DELETE /device-token
Body (JSON) or query: `device_id` (required).
200 `{"message":"Device unregistered.","removed":true}` (`removed:false` if the device wasn't registered).
