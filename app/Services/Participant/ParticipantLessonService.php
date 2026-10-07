<?php

namespace App\Services\Participant;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentAttemptFile;
use App\Models\Enrolment;
use App\Models\LearningFile;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\Files\FilePreviewService;
use App\Services\Learning\LearningFileService;
use App\Services\Learning\ModuleAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared lesson logic for the participant mobile API: access checks,
 * file resolution, serialisation and progress tracking.
 */
class ParticipantLessonService
{
    public function __construct(
        private readonly ModuleAccessService $moduleAccess,
        private readonly LearningFileService $files
    ) {}

    /**
     * Abort with a JSON-friendly HTTP error unless the participant may open the lesson.
     */
    public function authorize(User $user, Lesson $lesson, bool $enforceModuleLock = true): void
    {
        $lesson->loadMissing('module.course');
        $module = $lesson->module;

        abort_unless(
            $module && $module->course && $lesson->is_published && $module->is_published,
            404,
            'Lesson not found.'
        );

        abort_unless(
            $this->isEnrolled($user, $module->course_id),
            403,
            'You are not enrolled in the course for this lesson.'
        );

        if ($enforceModuleLock) {
            abort_unless(
                $this->moduleAccess->canAccess($module, $user),
                403,
                'This module is locked. Complete the previous module or wait for your instructor to release it.'
            );
        }
    }

    public function isEnrolled(User $user, int $courseId): bool
    {
        return Enrolment::where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * The lesson's material files (legacy single file_path included), in upload order.
     */
    public function lessonFiles(Lesson $lesson, ?Collection $learningFiles = null): Collection
    {
        if ($learningFiles === null || ($learningFiles->isEmpty() && $this->files->normalisePath($lesson->file_path) !== null)) {
            $this->files->syncLesson($lesson);
            $learningFiles = LearningFile::where('lesson_id', $lesson->id)->orderBy('id')->get();
        }

        return $learningFiles->filter(fn (LearningFile $file) => $this->learningFileExists($file))->values();
    }

    /**
     * Resolve the lesson's primary (first) file, or null when none exists on disk.
     *
     * @return array{disk:string,path:string,name:string,mime:?string,size:?int,learning_file_id:?int,downloadable:bool}|null
     */
    public function primaryFile(Lesson $lesson, ?Collection $learningFiles = null): ?array
    {
        $file = $this->lessonFiles($lesson, $learningFiles)->first();

        if (! $file) {
            return null;
        }

        return [
            'disk' => $file->diskName(),
            'path' => $file->path,
            'name' => $file->displayName(),
            'mime' => $file->mime_type,
            'size' => (int) $file->size_bytes,
            'learning_file_id' => $file->id,
            'downloadable' => $this->files->isDownloadable($file),
        ];
    }

    public function learningFileExists(LearningFile $file): bool
    {
        return $file->existsOnDisk();
    }

    /** API payload for one material or submission file. */
    public function presentFile(LearningFile|AssessmentAttemptFile $file, string $downloadPath, string $downloadUrl, ?bool $downloadable = null): array
    {
        $downloadable ??= $this->files->isDownloadable($file);

        return [
            'id' => $file->id,
            'name' => $file->displayName(),
            'mime_type' => $file->mime_type,
            'size_bytes' => (int) $file->size_bytes,
            // false = view-only: the endpoint always answers with Content-Disposition: inline.
            'downloadable' => $downloadable,
            'view_only' => ! $downloadable,
            'download_path' => $downloadPath,
            'download_url' => $downloadUrl,
        ];
    }

    /** Files a participant attached to one of their attempts. */
    public function presentAttemptFiles(AssessmentAttempt $attempt): array
    {
        return $this->files->attemptFiles($attempt)
            ->map(fn (AssessmentAttemptFile $file) => $this->presentFile(
                $file,
                "/submissions/files/{$file->id}",
                route('api.participant.submissions.files.download', $file),
                true
            ))
            ->values()
            ->all();
    }

    /**
     * Serialise a lesson for the mobile app.
     */
    public function present(
        Lesson $lesson,
        ?User $user = null,
        ?Collection $learningFiles = null,
        ?LessonProgress $progress = null,
        ?bool $isLocked = null,
        bool $includeContent = true
    ): array {
        $lesson->loadMissing('module');
        $learningFiles = $this->lessonFiles($lesson, $learningFiles);

        if ($user && ! $progress) {
            $progress = LessonProgress::where('lesson_id', $lesson->id)->where('user_id', $user->id)->first();
        }

        $primary = $this->primaryFile($lesson, $learningFiles);
        $downloadPath = $primary ? "/lessons/{$lesson->id}/download" : null;
        $downloadUrl = $primary ? route('api.participant.lessons.download', $lesson) : null;
        $externalFile = $this->isExternalUrl((string) $lesson->file_path) ? $lesson->file_path : null;

        $files = $learningFiles
            ->map(fn (LearningFile $file) => $this->presentFile(
                $file,
                "/lessons/{$lesson->id}/files/{$file->id}/download",
                route('api.participant.lessons.files.download', [$lesson, $file])
            ))
            ->values()
            ->all();

        $data = [
            'id' => $lesson->id,
            'course_module_id' => $lesson->course_module_id,
            'module_id' => $lesson->course_module_id,
            'course_id' => $lesson->module?->course_id,
            'title' => $lesson->title,
            'content_type' => $lesson->content_type,
            'type' => $lesson->content_type,
            'video_url' => $lesson->video_url,
            'external_url' => $lesson->external_url ?: $externalFile,
            'estimated_minutes' => $lesson->estimated_minutes,
            'duration_minutes' => $lesson->estimated_minutes,
            'position' => $lesson->position,
            'is_published' => (bool) $lesson->is_published,
            'is_locked' => $isLocked,
            'has_file' => $primary !== null,
            'file_name' => $primary['name'] ?? null,
            'file_mime_type' => $primary['mime'] ?? null,
            'file_size_bytes' => $primary['size'] ?? null,
            'file_downloadable' => $primary['downloadable'] ?? false,
            'resource_url' => $downloadUrl,
            'file_url' => $downloadUrl,
            'download_url' => $downloadUrl,
            'download_path' => $downloadPath,
            'files' => $files,
            'progress' => [
                'completed' => (bool) $progress?->completed_at,
                'completed_at' => $progress?->completed_at?->toIso8601String(),
                'first_opened_at' => $progress?->first_opened_at?->toIso8601String(),
                'last_opened_at' => $progress?->last_opened_at?->toIso8601String(),
                'time_spent_seconds' => (int) ($progress?->time_spent_seconds ?? 0),
            ],
            'created_at' => $lesson->created_at?->toIso8601String(),
            'updated_at' => $lesson->updated_at?->toIso8601String(),
        ];

        if ($includeContent) {
            $data['content'] = $lesson->content;
            $data['body'] = $lesson->content;
        }

        return $data;
    }

    /**
     * Stream a stored file with the correct headers. Local disks use BinaryFileResponse,
     * which supports HTTP Range requests (needed for in-app video/audio playback).
     *
     * View-only files ($downloadable = false) are always served inline with no-store
     * caching; an explicit ?download=1 for them is refused with 403.
     */
    public function fileResponse(string $disk, string $path, string $name, ?string $mime, bool $inline, bool $downloadable = true, bool $forceDownload = false): Response
    {
        abort_unless(Storage::disk($disk)->exists($path), 404, 'The file for this lesson is not available.');
        abort_if(! $downloadable && $forceDownload, 403, LearningFileService::VIEW_ONLY_MESSAGE);

        return $this->files->stream($disk, $path, $name, $mime, $inline || ! $downloadable, $downloadable);
    }

    /** Stream a material/submission file model through fileResponse(). */
    public function streamFile(LearningFile|AssessmentAttemptFile $file, Request $request, ?bool $downloadable = null): Response
    {
        // The mobile reader consumes office documents as a PDF so participants
        // can read them in-app without relying on another installed app.
        if ($request->query('format') === 'pdf'
            && in_array(strtolower(pathinfo($file->displayName(), PATHINFO_EXTENSION)), ['docx', 'odt', 'rtf'], true)) {
            abort_unless($file->existsOnDisk(), 404, 'The file is not available.');
            $storage = Storage::disk($file->diskName());
            $absolute = $storage->path($file->path);
            $pdf = app(FilePreviewService::class)->wordToPdf($absolute, $file->displayName());

            return response()->file($pdf, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.addslashes(pathinfo($file->displayName(), PATHINFO_FILENAME).'.pdf').'"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return $this->fileResponse(
            $file->diskName(),
            (string) $file->path,
            $file->displayName(),
            $file->mime_type,
            $request->boolean('inline'),
            $downloadable ?? $this->files->isDownloadable($file),
            $request->boolean('download')
        );
    }

    /** Upper bound for a single reading-time delta (seconds). Larger values are clamped. */
    public const MAX_TIME_DELTA_SECONDS = 3600;

    /**
     * Normalise the reading-time fields of a progress request/payload into the seconds to add.
     * "time_spent_seconds_delta" (clamped to 0..3600) wins over the legacy additive "time_spent_seconds".
     */
    public function secondsToAdd(array $input): int
    {
        if (array_key_exists('time_spent_seconds_delta', $input) && $input['time_spent_seconds_delta'] !== null) {
            return min(self::MAX_TIME_DELTA_SECONDS, max(0, (int) $input['time_spent_seconds_delta']));
        }

        return max(0, (int) ($input['time_spent_seconds'] ?? 0));
    }

    /**
     * Record lesson progress and recalculate the enrolment's course progress.
     * $completed = null leaves the completion state unchanged (reading-time only update).
     * $seconds is ADDED to the stored time_spent_seconds.
     */
    public function saveProgress(User $user, Lesson $lesson, ?bool $completed, int $seconds = 0): array
    {
        $lesson->loadMissing('module');
        $courseId = $lesson->module->course_id;
        $seconds = max(0, $seconds);

        $progress = DB::transaction(function () use ($user, $lesson, $completed, $seconds) {
            $existing = LessonProgress::where('lesson_id', $lesson->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $completedAt = match ($completed) {
                true => $existing?->completed_at ?? now(),
                false => null,
                null => $existing?->completed_at,
            };

            $progress = $existing ?? new LessonProgress(['lesson_id' => $lesson->id, 'user_id' => $user->id]);
            $progress->fill([
                'first_opened_at' => $existing?->first_opened_at ?? now(),
                'last_opened_at' => now(),
                'completed_at' => $completedAt,
            ]);
            $progress->save();

            if ($seconds > 0) {
                // Atomic increment so concurrent deltas are never lost.
                LessonProgress::whereKey($progress->id)->increment('time_spent_seconds', $seconds);
                $progress->refresh();
            }

            return $progress;
        });

        $total = DB::table('lessons')
            ->join('course_modules', 'course_modules.id', '=', 'lessons.course_module_id')
            ->where('course_modules.course_id', $courseId)
            ->where('lessons.is_published', true)
            ->count();

        $done = DB::table('lesson_progress')
            ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
            ->join('course_modules', 'course_modules.id', '=', 'lessons.course_module_id')
            ->where('course_modules.course_id', $courseId)
            ->where('lessons.is_published', true)
            ->where('lesson_progress.user_id', $user->id)
            ->whereNotNull('lesson_progress.completed_at')
            ->count();

        $percent = $total > 0 ? round(($done / $total) * 100, 2) : 0;

        $enrolment = Enrolment::where('course_id', $courseId)->where('user_id', $user->id)->first();

        if ($enrolment) {
            $enrolment->update([
                'progress_percent' => $percent,
                'status' => $percent >= 100 ? 'completed' : 'in_progress',
                'started_at' => $enrolment->started_at ?? now(),
                'completed_at' => $percent >= 100 ? ($enrolment->completed_at ?? now()) : null,
            ]);
        }

        return [
            'message' => 'Progress saved.',
            'lesson_id' => $lesson->id,
            'completed' => (bool) $progress->completed_at,
            'completed_at' => $progress->completed_at?->toIso8601String(),
            'time_spent_seconds' => (int) $progress->time_spent_seconds,
            'time_spent_seconds_added' => $seconds,
            'course_id' => $courseId,
            'course_progress_percent' => $percent,
            'course_status' => $enrolment?->status,
        ];
    }

    /**
     * Mark the lesson as opened (without completing it).
     */
    public function touchOpened(User $user, Lesson $lesson): LessonProgress
    {
        $progress = LessonProgress::firstOrNew(['lesson_id' => $lesson->id, 'user_id' => $user->id]);
        $progress->first_opened_at ??= now();
        $progress->last_opened_at = now();
        $progress->save();

        return $progress;
    }

    /**
     * Mobile-safe assessment payload: attachment_url points at the authenticated API route.
     */
    public function presentAssessment(Assessment $assessment): array
    {
        $data = $assessment->toArray();
        unset($data['files']);

        $attachments = $this->files->assessmentFiles($assessment);
        $first = $attachments->first();

        // Legacy single-attachment fields point at the first file.
        $data['attachment_url'] = $first ? route('api.participant.assignments.attachment', $assessment) : null;
        $data['attachment_download_path'] = $first ? "/assignments/{$assessment->id}/attachment" : null;
        $data['attachment_name'] = $first?->displayName();
        $data['attachment_downloadable'] = $first ? $this->files->isDownloadable($first) : false;
        $data['attachments'] = $attachments
            ->map(fn (LearningFile $file) => $this->presentFile(
                $file,
                "/assignments/{$assessment->id}/attachments/{$file->id}",
                route('api.participant.assignments.attachments.download', [$assessment, $file])
            ))
            ->values()
            ->all();

        return $data;
    }

    private function isExternalUrl(string $value): bool
    {
        return (bool) preg_match('#^https?://#i', trim($value));
    }
}
