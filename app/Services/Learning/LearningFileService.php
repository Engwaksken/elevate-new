<?php

namespace App\Services\Learning;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentAttemptFile;
use App\Models\LearningFile;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Files\FilePreviewService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;

/**
 * Lesson / assignment / submission files: the participant download policy,
 * multi-file uploads, legacy single-file syncing and HTTP responses.
 *
 * Policy (config/elearning.php "downloadable_extensions"): participants may
 * download spreadsheets and archives only. Everything else is view-only and
 * is served inline (never as an attachment). Course staff keep full download
 * access and participants can always download their own submissions.
 */
class LearningFileService
{
    public const VIEW_ONLY_MESSAGE = 'This file is view-only and cannot be downloaded.';

    /** Kinds a browser can render from inline bytes. */
    private const INLINE_KINDS = ['pdf', 'image', 'video', 'audio', 'text'];

    public function __construct(private readonly FilePreviewService $previews)
    {
    }

    /* ------------------------------------------------------------------
     | Policy
     * ------------------------------------------------------------------ */

    /** @return array<int,string> */
    public function downloadableExtensions(): array
    {
        return array_values(array_map(
            fn ($extension) => strtolower(ltrim((string) $extension, '.')),
            (array) config('elearning.downloadable_extensions', ['xlsx', 'xls', 'csv', 'zip'])
        ));
    }

    /** Whether participants may download a file with this name/extension. */
    public function isDownloadable(Model|string|null $file): bool
    {
        $extension = $file instanceof Model
            ? $file->extension()
            : strtolower(pathinfo((string) $file, PATHINFO_EXTENSION) ?: (string) $file);

        return $extension !== '' && in_array($extension, $this->downloadableExtensions(), true);
    }

    /**
     * Course staff: active staff who instruct the course, e-learning
     * administrators (courses.edit) or super administrators. Files without a
     * course are treated as staff-owned material.
     */
    public function canManageCourse(?User $user, ?int $courseId): bool
    {
        if (! $user || ! $user->isStaff() || ! $user->isActive()) {
            return false;
        }

        if (! $courseId) {
            return true;
        }

        if ($user->isSuperAdmin() || $user->hasPermission('courses.edit')) {
            return true;
        }

        return $user->instructedCourses()->whereKey($courseId)->exists();
    }

    public function canDownloadLearningFile(?User $user, LearningFile $file): bool
    {
        return $this->isDownloadable($file) || $this->canManageCourse($user, $file->course_id ? (int) $file->course_id : null);
    }

    /* ------------------------------------------------------------------
     | Uploads
     * ------------------------------------------------------------------ */

    public function maxFiles(): int
    {
        return max(1, (int) config('elearning.max_files_per_upload', 10));
    }

    /**
     * Validation rules for a multi-file field (and its legacy single-file twin).
     *
     * @param  string  $mimesKey  lesson_mimes | assignment_mimes | submission_mimes
     */
    public function uploadRules(string $field, string $mimesKey, ?string $legacyField = null): array
    {
        $fileRules = ['file', 'max:'.(int) config('elearning.max_file_kb', 51200), 'mimes:'.config("elearning.{$mimesKey}")];

        $rules = [
            $field => ['nullable', 'array', 'max:'.$this->maxFiles()],
            $field.'.*' => $fileRules,
        ];

        if ($legacyField) {
            $rules[$legacyField] = array_merge(['nullable'], $fileRules);
        }

        return $rules;
    }

    /** @return array<int,UploadedFile> */
    public function uploadedFiles(Request $request, string ...$fields): array
    {
        $files = [];

        foreach ($fields as $field) {
            foreach ((array) $request->file($field, []) as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    /**
     * Store lesson or assessment material files.
     *
     * @param  array<int,UploadedFile>  $uploads
     * @return Collection<int,LearningFile>
     */
    public function storeMaterials(array $uploads, int $courseId, ?Lesson $lesson = null, ?Assessment $assessment = null, ?User $by = null): Collection
    {
        $disk = (string) config('elearning.disk', 'local');
        $directory = $lesson
            ? "learning/{$courseId}/lessons/{$lesson->id}"
            : "learning/{$courseId}/assessments/".($assessment?->id ?? 'general');

        return collect($uploads)->map(function (UploadedFile $upload) use ($disk, $directory, $courseId, $lesson, $assessment, $by) {
            $extension = strtolower($upload->getClientOriginalExtension() ?: ($upload->guessExtension() ?: 'bin'));
            $stored = Str::uuid().'.'.$extension;
            $path = $upload->storeAs($directory, $stored, $disk);

            return LearningFile::create([
                'course_id' => $courseId,
                'lesson_id' => $lesson?->id,
                'assessment_id' => $assessment?->id,
                'original_name' => Str::limit($upload->getClientOriginalName() ?: $stored, 250, ''),
                'stored_name' => $stored,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $upload->getMimeType(),
                'size_bytes' => (int) $upload->getSize(),
                'uploaded_by' => $by?->id,
            ]);
        })->values();
    }

    /**
     * Store a participant's submission files on the private disk.
     *
     * @param  array<int,UploadedFile>  $uploads
     * @return Collection<int,AssessmentAttemptFile>
     */
    public function storeSubmissionFiles(AssessmentAttempt $attempt, array $uploads): Collection
    {
        $disk = (string) config('elearning.disk', 'local');
        $directory = 'participant-submissions/'.$attempt->user_id.'/'.$attempt->assessment_id;

        return collect($uploads)->map(function (UploadedFile $upload) use ($attempt, $disk, $directory) {
            $extension = strtolower($upload->getClientOriginalExtension() ?: ($upload->guessExtension() ?: 'bin'));
            $path = $upload->storeAs($directory, Str::uuid().'.'.$extension, $disk);

            return $attempt->files()->create([
                'original_name' => Str::limit($upload->getClientOriginalName() ?: basename($path), 250, ''),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $upload->getMimeType(),
                'size_bytes' => (int) $upload->getSize(),
            ]);
        })->values();
    }

    /** Delete one material file (stored object + row) and clear a matching legacy column. */
    public function deleteLearningFile(LearningFile $file): void
    {
        if ($file->lesson_id) {
            Lesson::whereKey($file->lesson_id)->where('file_path', $file->path)->update(['file_path' => null]);
            Lesson::whereKey($file->lesson_id)->where('file_path', 'storage/'.$file->path)->update(['file_path' => null]);
        }

        if ($file->assessment_id) {
            Assessment::whereKey($file->assessment_id)->where('attachment_path', $file->path)->update(['attachment_path' => null]);
        }

        $file->deleteStoredFile();
        $file->delete();
    }

    /** Delete every material file of a lesson or assessment (used before deleting the parent). */
    public function deleteAllFor(Lesson|Assessment $owner): void
    {
        $owner->files()->get()->each(fn (LearningFile $file) => $file->deleteStoredFile());
        $owner->files()->delete();
    }

    /* ------------------------------------------------------------------
     | Legacy single-file columns -> rows
     | The migration copies existing values; these keep values written later
     | through legacy paths (e.g. the admin "file path" text field) visible.
     * ------------------------------------------------------------------ */

    public function syncLesson(Lesson $lesson): void
    {
        $this->syncLessons(collect([$lesson]));
    }

    public function syncLessons(iterable $lessons): void
    {
        $candidates = collect($lessons)
            ->filter(fn ($lesson) => $lesson instanceof Lesson && $this->normalisePath($lesson->file_path) !== null)
            ->values();

        if ($candidates->isEmpty()) {
            return;
        }

        $existing = LearningFile::query()
            ->whereIn('lesson_id', $candidates->pluck('id'))
            ->get(['lesson_id', 'path'])
            ->map(fn ($row) => $row->lesson_id.'|'.$row->path)
            ->flip();

        foreach ($candidates as $lesson) {
            $path = $this->normalisePath($lesson->file_path);

            if (isset($existing[$lesson->id.'|'.$path]) || ! ($disk = $this->locate($path, ['public', 'local']))) {
                continue;
            }

            $lesson->loadMissing('module');
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $base = Str::slug((string) $lesson->title) ?: 'lesson-'.$lesson->id;

            LearningFile::create([
                'course_id' => $lesson->module?->course_id,
                'lesson_id' => $lesson->id,
                'original_name' => $extension ? "{$base}.{$extension}" : $base,
                'stored_name' => basename($path),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $this->safeMime($disk, $path),
                'size_bytes' => $this->safeSize($disk, $path),
                'migrated_from' => 'lessons.file_path',
            ]);

            $lesson->unsetRelation('files');
        }
    }

    public function syncAssessments(iterable $assessments): void
    {
        $candidates = collect($assessments)
            ->filter(fn ($assessment) => $assessment instanceof Assessment && $this->normalisePath($assessment->attachment_path) !== null)
            ->values();

        if ($candidates->isEmpty()) {
            return;
        }

        $existing = LearningFile::query()
            ->whereIn('assessment_id', $candidates->pluck('id'))
            ->get(['assessment_id', 'path'])
            ->map(fn ($row) => $row->assessment_id.'|'.$row->path)
            ->flip();

        foreach ($candidates as $assessment) {
            $path = $this->normalisePath($assessment->attachment_path);

            if (isset($existing[$assessment->id.'|'.$path]) || ! ($disk = $this->locate($path, ['public', 'local']))) {
                continue;
            }

            LearningFile::create([
                'course_id' => $assessment->course_id,
                'assessment_id' => $assessment->id,
                'original_name' => basename($path),
                'stored_name' => basename($path),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $this->safeMime($disk, $path),
                'size_bytes' => $this->safeSize($disk, $path),
                'migrated_from' => 'assessments.attachment_path',
            ]);

            $assessment->unsetRelation('files');
        }
    }

    public function syncAttempts(iterable $attempts): void
    {
        $candidates = collect($attempts)
            ->filter(fn ($attempt) => $attempt instanceof AssessmentAttempt && $this->normalisePath($attempt->submission_file_path) !== null)
            ->values();

        if ($candidates->isEmpty()) {
            return;
        }

        $existing = AssessmentAttemptFile::query()
            ->whereIn('assessment_attempt_id', $candidates->pluck('id'))
            ->get(['assessment_attempt_id', 'path'])
            ->map(fn ($row) => $row->assessment_attempt_id.'|'.$row->path)
            ->flip();

        foreach ($candidates as $attempt) {
            $path = $this->normalisePath($attempt->submission_file_path);

            if (isset($existing[$attempt->id.'|'.$path]) || ! ($disk = $this->locate($path, ['local', 'public']))) {
                continue;
            }

            AssessmentAttemptFile::create([
                'assessment_attempt_id' => $attempt->id,
                'original_name' => basename($path),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $this->safeMime($disk, $path),
                'size_bytes' => $this->safeSize($disk, $path),
                'migrated_from' => 'assessment_attempts.submission_file_path',
            ]);

            $attempt->unsetRelation('files');
        }
    }

    /** Synced material files of a lesson that exist on disk. */
    public function lessonFiles(Lesson $lesson): Collection
    {
        $this->syncLesson($lesson);

        return $lesson->files()->get()->filter(fn (LearningFile $file) => $file->existsOnDisk())->values();
    }

    public function assessmentFiles(Assessment $assessment): Collection
    {
        $this->syncAssessments([$assessment]);

        return $assessment->files()->get()->filter(fn (LearningFile $file) => $file->existsOnDisk())->values();
    }

    public function attemptFiles(AssessmentAttempt $attempt): Collection
    {
        $this->syncAttempts([$attempt]);

        return $attempt->files()->get()->filter(fn (AssessmentAttemptFile $file) => $file->existsOnDisk())->values();
    }

    /* ------------------------------------------------------------------
     | Presentation
     * ------------------------------------------------------------------ */

    /**
     * View data for one file. "can_download" already accounts for the viewer.
     */
    public function present(Model $file, ?User $viewer = null, ?bool $canDownload = null): array
    {
        $isSubmission = $file instanceof AssessmentAttemptFile;
        $canDownload ??= $isSubmission
            ? true // only the owner or course staff can reach a submission file
            : $this->canDownloadLearningFile($viewer, $file);

        $route = $isSubmission ? 'learning.submissions.files.show' : 'learning.files.download';
        $kind = $this->previews->kind($file->displayName());

        return [
            'id' => $file->id,
            'name' => $file->displayName(),
            'extension' => $file->extension(),
            'kind' => $kind,
            'mime_type' => $file->mime_type,
            'size_bytes' => (int) $file->size_bytes,
            'size_label' => $this->sizeLabel((int) $file->size_bytes),
            'downloadable' => $this->isDownloadable($file),
            'can_download' => $canDownload,
            'view_url' => route($route, [$file, 'preview' => 1]),
            'download_url' => $canDownload ? route($route, [$file, 'download' => 1]) : null,
            'model' => $file,
        ];
    }

    public function sizeLabel(int $bytes): string
    {
        return match (true) {
            $bytes >= 1048576 => number_format($bytes / 1048576, 1).' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 1).' KB',
            $bytes > 0 => $bytes.' B',
            default => '',
        };
    }

    /* ------------------------------------------------------------------
     | Responses
     * ------------------------------------------------------------------ */

    /**
     * Web response for a stored file.
     *
     * - ?preview=1 / ?preview=raw: in-browser preview (download button hidden when not allowed).
     * - allowed + no inline flag: attachment download (unchanged behaviour).
     * - not allowed + ?download=1: 403.
     * - not allowed otherwise: inline bytes for PDF/image/audio/video/text,
     *   the preview page for everything else (Word converts to PDF; other
     *   Office files show a "view-only" notice).
     */
    public function respond(Request $request, Model $file, bool $allowDownload): Response
    {
        abort_unless($file->existsOnDisk(), 404, 'The file is not available.');

        $disk = $file->diskName();
        $name = $file->displayName();

        if ($this->previews->wantsPreview($request)) {
            return $this->previews->respond($request, $disk, $file->path, $name, $allowDownload);
        }

        if ($allowDownload && ! $request->boolean('inline')) {
            return $this->stream($disk, $file->path, $name, $file->mime_type, false, true);
        }

        abort_if(! $allowDownload && $request->boolean('download'), 403, self::VIEW_ONLY_MESSAGE);

        if (in_array($this->previews->kind($name), self::INLINE_KINDS, true)) {
            return $this->stream($disk, $file->path, $name, $file->mime_type, true, $allowDownload);
        }

        return $this->previews->respond($request, $disk, $file->path, $name, $allowDownload);
    }

    /**
     * Stream bytes. BinaryFileResponse supports HTTP Range requests (audio/video seeking).
     * View-only responses are never cached and always carry Content-Disposition: inline.
     */
    public function stream(string $disk, string $path, string $name, ?string $mime, bool $inline, bool $allowDownload = true): Response
    {
        $storage = Storage::disk($disk);
        abort_unless($storage->exists($path), 404, 'The file is not available.');

        $inline = $inline || ! $allowDownload;
        $kind = $this->previews->kind($name);
        $mime = $kind === 'text'
            ? 'text/plain; charset=UTF-8'
            : ($this->mimeFor($name) ?: $mime ?: ($storage->mimeType($path) ?: 'application/octet-stream'));

        $headers = [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $allowDownload ? 'private, max-age=0, must-revalidate' : 'private, no-store, max-age=0',
        ];

        if (! $allowDownload) {
            $headers['Pragma'] = 'no-cache';
            $headers['X-Download-Options'] = 'noopen';
        }

        if ($kind === 'image') {
            $headers['Content-Security-Policy'] = "default-src 'none'; style-src 'unsafe-inline'; sandbox";
        }

        $disposition = $inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        $fallback = preg_replace('/[^\x20-\x7e]|[%\/\\\\"]/', '_', Str::ascii($name) ?: 'file');

        if (config("filesystems.disks.{$disk}.driver") === 'local') {
            $response = new BinaryFileResponse($storage->path($path), 200, $headers);
            $response->setContentDisposition($disposition, str_replace(['/', '\\'], '_', $name), $fallback);

            return $response;
        }

        return $storage->response($path, $name, $headers, $disposition);
    }

    /* ------------------------------------------------------------------
     | Helpers
     * ------------------------------------------------------------------ */

    public function normalisePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '' || preg_match('#^https?://#i', $path)) {
            return null;
        }

        return ltrim((string) preg_replace('#^/?storage/#', '', $path), '/');
    }

    private function locate(string $path, array $disks): ?string
    {
        foreach ($disks as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return $disk;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function safeMime(string $disk, string $path): ?string
    {
        try {
            return Storage::disk($disk)->mimeType($path) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function safeSize(string $disk, string $path): int
    {
        try {
            return (int) Storage::disk($disk)->size($path);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function mimeFor(string $name): ?string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($extension === '') {
            return null;
        }

        return MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? null;
    }
}
