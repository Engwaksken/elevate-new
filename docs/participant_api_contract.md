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
**v2:** `completed` is now optional and `time_spent_seconds_delta` / `client_operation_id` were added. See "Reading time" in the v2 section below.
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

Each notification has `id`, `type`, `title`, `message`, `action_url` (a web URL, may be null), `data` (JSON string or null), `read_at`, `created_at`, `updated_at`. `type` values sent to participants, with the ids found in `data`:

| `type` | When | `data` keys |
|---|---|---|
| `enrolment` | Enrolled in a course, or enrolment status changed (active, completed, withdrawn, cancelled, failed) | `course_id`, `enrolment_id` (absent for bulk imports), `status`, `event` |
| `lesson_published` | A lesson is published in an enrolled course | `course_id`, `module_id`, `lesson_id` |
| `assignment_published` | An assignment/quiz/exam is published | `course_id`, `assessment_id`, `due_at` |
| `assignment_due_date_changed` | A published assessment's due date changes | `course_id`, `assessment_id`, `due_at` |
| `assignment_graded` / `assignment_feedback` | An instructor grades or comments on a submission | `course_id`, `assessment_id`, `attempt_id`, `score`, `percentage` |
| `assignment_extension_approved` / `assignment_extension_rejected` | Extension request reviewed | `course_id`, `assessment_id`, `extension_request_id`, `approved_due_at` |
| `course_announcement` | A course announcement is posted (scheduled ones are not notified) | `course_id`, `announcement_id` |
| `course_application` | A course-call application is reviewed | `course_call_id`, `course_application_id`, `status` |
| `mentorship_match` / `mentorship_session` | Match created/changed; session scheduled, rescheduled or status changed | `mentor_match_id`, `mentorship_session_id`, `scheduled_at`, `status` |
| `job_application` | Job application status changed | `job_id`, `job_application_id`, `status` |
| `certificate`, `event_reminder` | Certificate issued; event reminder | see payload |

Unknown types should be shown with their `title` and `message`.

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
**v2:** there is also an `assignment_submission` type, and `lesson_progress` accepts `time_spent_seconds_delta` (see the v2 section).
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

---

## v2 additions (profile, tracking, extensions)

All paths below are under the same authenticated participant base (`/api/v1/participant`) and use the same auth, error format and conventions as above. They **only add** fields to existing responses. Nothing existing was renamed or removed.
Tests: `tests/Feature/Participant/ParticipantV2ApiTest.php`.

Business-rule 422 responses in this section carry a machine-readable `code` next to `message`, e.g. `{"message":"...","code":"overdue"}`. Validation 422s keep the usual `{"message","errors":{...}}` shape.

### Profile

#### GET /profile
200:
```json
{
  "profile": {
    "id": 5,
    "name": "Jane Achieng",
    "email": "jane@x.org",
    "phone": "0700000000",
    "photo_url": "https://site.elevateher360.org/api/v1/participant/profile/photo?v=1727690000",
    "photo_path": "/profile/photo?v=1727690000",
    "has_photo": true,
    "surname": "Achieng", "given_name": "Jane", "other_name": null,
    "gender": "female",
    "date_of_birth": "1998-04-12",
    "country": "Kenya", "district": "Kisumu", "location": "Kondele",
    "education_level": "Diploma",
    "employment_status": "Self-employed",
    "career_interests": "Digital marketing, bookkeeping",
    "preferred_language": "English",
    "is_pwd": false,
    "disability_types": [],
    "disability_other": null,
    "branch": {"id": 2, "name": "Kisumu"},
    "user_type": "participant",
    "status": "active",
    "updated_at": "2026-09-30T10:00:00+00:00"
  },
  "editable_fields": ["name","phone","surname","given_name","other_name","gender","date_of_birth","country","district","location","education_level","employment_status","career_interests","preferred_language","is_pwd","disability_types","disability_other"]
}
```
- `email`, `branch`, `user_type` and `status` are read-only because admins own them. There is no `bio` column. Use `career_interests` as the free-text "about me / interests" field.
- `photo_url` is an **authenticated** URL (send the Bearer token), or `null`. The `?v=` suffix changes whenever the photo changes, so it can be used as a cache key. `photo_path` is the same URL relative to the participant base.
- `gender`: `female|male|other|prefer_not_to_say|null`. `date_of_birth`: `YYYY-MM-DD|null`.
- `is_pwd`, `disability_types` and `disability_other` are optional and sensitive. Show them only on the participant's own profile screen.
- A participant with no `profiles` row gets the same shape with `null` values. The row is created on her first update.

#### PUT /profile  (JSON)
Partial update: send only the fields that change. Fields that are not in `editable_fields` (e.g. `email`, `branch_id`) are ignored.
Rules:
- `name`: string ≤255, cannot be empty
- `phone`: ≤30
- `surname`, `given_name`, `other_name`: ≤120
- `gender`: the enum above
- `date_of_birth`: a date after 1900-01-01 and before today
- `country`, `district`, `location`, `education_level`, `employment_status`: ≤190
- `career_interests`: ≤2000
- `preferred_language`: ≤50 (null resets it to `English`)
- `is_pwd`: boolean
- `disability_types`: array of up to 20 strings, each ≤100
- `disability_other`: ≤255

200: same body as GET /profile. 422: `{"message":"...","errors":{"gender":["The selected gender is invalid."]}}`.

#### POST /profile/photo  (multipart/form-data)
Field `photo`: required, `jpg|jpeg|png|webp`, ≤ 5 MB. The photo is stored on the private (`local`) disk and the previous photo is deleted.
200 `{"message":"Profile photo updated.","profile":{...},"editable_fields":[...]}` · 422 validation.

#### GET /profile/photo
Streams the photo (auth required, `Content-Disposition: inline`). 404 `{"message":"No profile photo."}` if there is none.

#### DELETE /profile/photo
200 `{"message":"Profile photo removed.","profile":{...},"editable_fields":[...]}`. It is idempotent and also succeeds when there was no photo.

#### PUT /profile/password  (JSON)
Body: `current_password`, `password`, `password_confirmation`. `password` uses Laravel `Password::defaults()` (min 8) and must differ from the current one.
204 with no body on success. The current token stays valid; the participant's **other** API tokens are revoked.
422 `{"message":"The current password is incorrect.","errors":{"current_password":["The current password is incorrect."]}}`, or the usual password rule errors.

### GET /progress
```json
{
  "summary": {
    "courses_enrolled": 2, "courses_completed": 0,
    "lessons_total": 12, "lessons_completed": 5, "time_spent_seconds": 5400,
    "assignments_total": 4, "assignments_submitted": 2, "assignments_graded": 1,
    "assignments_pending": 1, "assignments_overdue": 1,
    "extension_requests_pending": 1,
    "mentorship_sessions_total": 6, "mentorship_sessions_attended": 3,
    "mentorship_sessions_missed": 1, "mentorship_sessions_upcoming": 2,
    "events_attended": 1
  },
  "courses": [
    {"id":1,"title":"Digital Marketing","status":"in_progress","progress_percent":41.67,"lessons_total":12,"lessons_completed":5,
     "time_spent_seconds":5400,"last_activity_at":"2026-09-30T10:00:00+00:00"}
  ],
  "recent_activity": [
    {"type":"lesson_completed","title":"Lesson 3","at":"2026-09-30T10:00:00+00:00","source_id":3,"course_id":1},
    {"type":"assignment_submitted","title":"Brief","at":"...","source_id":7,"course_id":1},
    {"type":"session_attended","title":"Goal setting","at":"...","source_id":4,"course_id":null}
  ]
}
```
Definitions:
- A lesson counts only if the lesson **and** its module are published and the participant is enrolled in its course. `time_spent_seconds` is the sum of `lesson_progress.time_spent_seconds` over those lessons. `courses[].progress_percent` = lessons_completed / lessons_total × 100, rounded to 2 decimal places (0 when the course has no lessons). `last_activity_at` is the latest lesson-progress update or submission in that course, or null.
- Assignments are the published assessments (`assignment|quiz|exam`) in enrolled courses, the same set as GET /assignments.
  - `submitted`: has at least one submitted or graded attempt.
  - `graded`: has a graded attempt.
  - `pending`: not submitted and not overdue.
  - `overdue`: not submitted and the effective due date (see below) has passed.
  - `submitted + pending + overdue = total`.
- Mentorship sessions are the sessions in matches where she is the mentee.
  - `attended`: `mentee_attended = true`.
  - `missed`: `mentee_attended = false`, or status `missed` without `mentee_attended = true`.
  - `upcoming`: status `scheduled` and `scheduled_at` in the future.
- `events_attended`: the number of distinct events where she has a `present` or `late` attendance record.
- `recent_activity`: the latest 10 items across the three types, newest first. `source_id` is the lesson, assessment or session id.

### Reading time: PUT|POST /lessons/{lesson}/progress (extended)
Body (JSON). Every field is optional, but at least one of `completed`, `time_spent_seconds_delta` or `time_spent_seconds` must be sent (otherwise 422 with `errors.completed`):
- `completed` boolean. **If omitted, the completion state is left unchanged.** It used to be required. `completed:false` still un-completes the lesson.
- `time_spent_seconds_delta` integer ≥ 0. Values above 3600 are clamped to 3600 per call; negatives → 422. The delta is **added** to `lesson_progress.time_spent_seconds`.
- `time_spent_seconds` (legacy) integer ≥ 0. It is also added to the total, as before. It is ignored when `time_spent_seconds_delta` is present, so never send both.
- `client_operation_id` (optional string ≤190): idempotency key. If the same key was already processed (here or through /offline-actions), the stored result is returned with `"duplicate": true` and nothing is added again.

The delta is additive, so the client must send only the seconds **not yet sent**: keep a per-lesson counter of unsent seconds and reset it after a 2xx. Send it when the lesson closes or the app goes to the background, and about every 60 s while the lesson is open. Use a new `client_operation_id` for each flush so that retries are safe.
The 200 response is unchanged, plus it now always includes `time_spent_seconds` (the new total) and `time_spent_seconds_added` (what was actually added after clamping).

Offline: the `lesson_progress` operation payload accepts the same fields: `{lesson_id, completed?, time_spent_seconds_delta?, time_spent_seconds?}`. The operation's `client_operation_id` already makes it idempotent.

### Mentorship (extended)
`GET /mentorship`: each `sessions[]` item has these fields (the ones marked * are new): `id, mentor_match_id, title, agenda, scheduled_at, duration_minutes, meeting_link, venue, status (scheduled|completed|cancelled|missed), session_notes, agreed_actions, next_session_at, updated_at, mentor_id, mentor_name, mentee_attended* (true|false|null), mentor_attended* (true|false|null), can_confirm_attendance* (bool)`. `/dashboard.next_mentorship_session` and `/sync.mentorship` use the same session shape, without `can_confirm_attendance`.

#### POST /mentorship/sessions/{session}/attendance
Body: `{"attended": true|false}` (required boolean).
- 404 `{"message":"Mentorship session not found."}`
- 403 `{"message":"You are not the mentee for this session."}`
- 422 `{"message":"This session has not started yet.","code":"session_not_started"}` (scheduled_at > now)
- 422 `{"message":"Attendance can only be confirmed within 14 days of the session.","code":"attendance_window_closed"}`
- 422 `{"message":"This session was cancelled.","code":"session_cancelled"}`
- 200 `{"message":"Attendance recorded.","session":{<session object as in GET /mentorship>}}`

It sets only `mentee_attended`. The mentee never changes the session `status`; the mentor or staff mark sessions completed or missed. She can change her answer while the 14-day window is open.

### Assignments: effective due date, overdue blocking, extension requests

> Deployment: run the migrations `2026_09_30_170000_create_assignment_extension_requests_table` and `2026_09_30_170100_add_photo_path_to_profiles_table` before deploying this code, because `/assignments`, `/sync`, `/courses/{id}` and `/progress` read the new table.

**Effective due date** for a participant = the `approved_due_at` of her latest approved extension request for that assessment, or else `assessment.due_at` (null means no deadline).

#### Assessment object (extended)
Every item of `GET /assignments`, `GET /sync` → `assignments[]` and `GET /courses/{course}` → `course.assessments[]` also has:
```json
{"effective_due_at":"2026-10-03T12:00:00+00:00"|null,
 "is_overdue":false,
 "can_submit":true,
 "submissions_count":0,
 "attempts_remaining":1,
 "extension_request": null | {"id":4,"assessment_id":3,"status":"pending|approved|rejected","reason":"...","requested_due_at":"...|null",
                              "approved_due_at":"...|null","reviewer_note":"...|null","created_at":"...","reviewed_at":"...|null"},
 "can_request_extension":false}
```
- `is_overdue`: the effective due date is in the past.
- `can_submit`: published AND not overdue AND `submissions_count < max_attempts`.
- `extension_request`: her latest request for this assessment (any status), or null.
- `can_request_extension`: no pending request AND attempts remain AND (overdue OR the effective due date is within 48 h).
- `is_graded`: the latest attempt has status `graded` or a `graded_at`.
- `latest_submission`: null, or `{id, attempt_number, status, submitted_at, graded_at, score, percentage, instructor_feedback}` for her latest attempt. `score`, `percentage` and `instructor_feedback` are null until the attempt is graded.

#### POST /assignments/{assessment}/submit (extended)
- New optional field `client_created_at` (ISO-8601): when the submission was made on the device. It is only honoured together with `client_submission_id`, i.e. for a queued offline submission.
- If the effective due date has passed → **422** `{"message":"This assignment is past its due date. Request an extension from your instructor.","code":"overdue"}`.
- **Offline grace window**: a queued submission is still accepted after the deadline if all of these hold:
  - `client_created_at` ≤ the effective due date;
  - the server receives it within **72 hours** of `client_created_at`;
  - `client_created_at` is not in the future (5-minute allowance for clock skew).

  Otherwise it is rejected with `code: "overdue"`.
- The existing rules are unchanged. `Maximum attempts reached.` now also carries `"code":"max_attempts_reached"`. A repeated `client_submission_id` still returns 200 with `duplicate:true`, and that check runs before the deadline check.

#### POST /offline-actions: new `assignment_submission` operation
`{"client_operation_id":"uuid","type":"assignment_submission","client_created_at":"ISO-8601","payload":{"assessment_id":3,"submission_text":"...","client_created_at":"ISO-8601"}}`
- Text only. `client_created_at` may be on the operation or in the payload.
- The `client_operation_id` is used as the `client_submission_id`, and the same deadline and grace-window rules apply.
- On failure the result is `{"status":"failed","code":422,"message":"This assignment is past its due date. ...","error_code":"overdue"}`.
- Submissions with a **file** must still go to `POST /assignments/{id}/submit` (multipart) with `client_submission_id` + `client_created_at`.

#### POST /assignments/{assessment}/extension-requests
Body (JSON): `reason` (required, 10–1000 chars), `requested_due_at` (optional datetime, must be in the future).
- 201 `{"message":"Extension request sent to your instructor.","extension_request":{...object above}}`
- 403 not enrolled · 404 `{"message":"Assessment not found."}` (missing or unpublished)
- 422 `{"message":"You already have a pending extension request for this assignment.","code":"extension_pending"}`
- 422 `{"message":"Extensions can only be requested for assignments that are overdue or due within 48 hours.","code":"extension_not_needed"}`
- 422 `{"message":"Maximum attempts reached.","code":"max_attempts_reached"}`
- 422 validation (`reason`, `requested_due_at`)

When a request is created, the course instructors get an in-app notification (`type: assignment_extension_requested`). When an instructor approves or rejects it, the participant gets a `user_notifications` row, which `GET /notifications` returns:
- `type`: `assignment_extension_approved` or `assignment_extension_rejected`
- `data`: `{"assessment_id","course_id","extension_request_id","approved_due_at"}`

The backend has no server push (FCM) sender yet, so the app should pick these up through `/notifications` and `/sync`.

## Help & support

`GET /api/v1/participant/support` (authenticated) returns the contacts managed in **Admin → Support Settings**, the same values as the web page `/participant/help`.

```json
{"support": {
  "email": "support@elevateher360.org", "alternate_email": null,
  "phone": "+256 700 000 001", "whatsapp": "+256 700 000 002",
  "whatsapp_url": "https://wa.me/256700000002",
  "branch": null, "address": null, "hours": "Mon–Fri, 8am–5pm",
  "introduction": "Contact the ElevateHer360 support team if you need assistance.",
  "technical": null,
  "help_page_url": "https://site.elevateher360.org/participant/help",
  "privacy_policy_url": "https://site.elevateher360.org/privacy-policy",
  "terms_url": "https://site.elevateher360.org/terms"
}}
```

Blank settings are `null`. `email` falls back to `LEGAL_SUPPORT_EMAIL` and `introduction` to the default text above. `whatsapp_url` is built from the digits in `whatsapp`.
