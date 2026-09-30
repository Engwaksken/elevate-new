<?php

namespace App\Services\Participant;

use App\Models\Assessment;
use App\Models\Enrolment;
use App\Models\LearningFile;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\Learning\ModuleAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Shared lesson logic for the participant mobile API: access checks,
 * file resolution, serialisation and progress tracking.
 */
class ParticipantLessonService
{
    public function __construct(private readonly ModuleAccessService $moduleAccess)
    {
    }

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
     * Resolve the lesson's primary downloadable file, or null when none exists on disk.
     *
     * @return array{disk:string,path:string,name:string,mime:?string,size:?int,learning_file_id:?int}|null
     */
    public function primaryFile(Lesson $lesson, ?Collection $learningFiles = null): ?array
    {
        $path = trim((string) $lesson->file_path);

        if ($path !== '' && ! $this->isExternalUrl($path)) {
            $path = ltrim(preg_replace('#^/?storage/#', '', $path), '/');

            foreach (['public', 'local'] as $disk) {
                if (Storage::disk($disk)->exists($path)) {
                    $extension = pathinfo($path, PATHINFO_EXTENSION);
                    $base = Str::slug($lesson->title) ?: 'lesson-'.$lesson->id;

                    return [
                        'disk' => $disk,
                        'path' => $path,
                        'name' => $extension ? "{$base}.{$extension}" : $base,
                        'mime' => Storage::disk($disk)->mimeType($path) ?: null,
                        'size' => Storage::disk($disk)->size($path),
                        'learning_file_id' => null,
                    ];
                }
            }
        }

        $learningFiles ??= LearningFile::where('lesson_id', $lesson->id)->orderBy('id')->get();

        foreach ($learningFiles as $file) {
            if ($this->learningFileExists($file)) {
                return [
                    'disk' => $file->disk ?: 'local',
                    'path' => $file->path,
                    'name' => $file->original_name ?: $file->stored_name,
                    'mime' => $file->mime_type,
                    'size' => (int) $file->size_bytes,
                    'learning_file_id' => $file->id,
                ];
            }
        }

        return null;
    }

    public function learningFileExists(LearningFile $file): bool
    {
        return $file->path && Storage::disk($file->disk ?: 'local')->exists($file->path);
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
        $learningFiles ??= LearningFile::where('lesson_id', $lesson->id)->orderBy('id')->get();

        if ($user && ! $progress) {
            $progress = LessonProgress::where('lesson_id', $lesson->id)->where('user_id', $user->id)->first();
        }

        $primary = $this->primaryFile($lesson, $learningFiles);
        $downloadPath = $primary ? "/lessons/{$lesson->id}/download" : null;
        $downloadUrl = $primary ? route('api.participant.lessons.download', $lesson) : null;
        $externalFile = $this->isExternalUrl((string) $lesson->file_path) ? $lesson->file_path : null;

        $files = $learningFiles
            ->filter(fn (LearningFile $file) => $this->learningFileExists($file))
            ->map(fn (LearningFile $file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size_bytes' => (int) $file->size_bytes,
                'download_path' => "/lessons/{$lesson->id}/files/{$file->id}/download",
                'download_url' => route('api.participant.lessons.files.download', [$lesson, $file]),
            ])
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
     */
    public function fileResponse(string $disk, string $path, string $name, ?string $mime, bool $inline): Response
    {
        $storage = Storage::disk($disk);
        abort_unless($storage->exists($path), 404, 'The file for this lesson is not available.');

        $mime = $mime ?: ($storage->mimeType($path) ?: 'application/octet-stream');
        $disposition = $inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        $fallbackName = Str::ascii($name) ?: 'download';

        if (config("filesystems.disks.{$disk}.driver") === 'local') {
            $response = new BinaryFileResponse($storage->path($path), 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]);
            $response->setContentDisposition($disposition, $name, preg_replace('/[^\x20-\x7e]|[%\/\\\\]/', '_', $fallbackName));

            return $response;
        }

        return $storage->response($path, $name, ['Content-Type' => $mime], $disposition);
    }

    /**
     * Record lesson progress and recalculate the enrolment's course progress.
     */
    public function saveProgress(User $user, Lesson $lesson, bool $completed, int $seconds = 0): array
    {
        $lesson->loadMissing('module');
        $courseId = $lesson->module->course_id;

        $existing = LessonProgress::where('lesson_id', $lesson->id)->where('user_id', $user->id)->first();

        $progress = LessonProgress::updateOrCreate(
            ['lesson_id' => $lesson->id, 'user_id' => $user->id],
            [
                'first_opened_at' => $existing?->first_opened_at ?? now(),
                'last_opened_at' => now(),
                'completed_at' => $completed ? ($existing?->completed_at ?? now()) : null,
                'time_spent_seconds' => (int) ($existing?->time_spent_seconds ?? 0) + max(0, $seconds),
            ]
        );

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
        $hasAttachment = $assessment->attachment_path
            && Storage::disk('public')->exists($assessment->attachment_path);

        $data['attachment_url'] = $hasAttachment
            ? route('api.participant.assignments.attachment', $assessment)
            : null;
        $data['attachment_download_path'] = $hasAttachment ? "/assignments/{$assessment->id}/attachment" : null;
        $data['attachment_name'] = $hasAttachment ? basename($assessment->attachment_path) : null;

        return $data;
    }

    private function isExternalUrl(string $value): bool
    {
        return (bool) preg_match('#^https?://#i', trim($value));
    }
}
