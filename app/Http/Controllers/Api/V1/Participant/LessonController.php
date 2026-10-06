<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttemptFile;
use App\Models\LearningFile;
use App\Models\Lesson;
use App\Services\Participant\ParticipantLessonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonController extends Controller
{
    public function __construct(private readonly ParticipantLessonService $lessons)
    {
    }

    /**
     * GET /lessons/{lesson} — full lesson detail (text body, file/video links, progress).
     */
    public function show(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $this->lessons->authorize($user, $lesson);

        $progress = $this->lessons->touchOpened($user, $lesson);

        return response()->json([
            'lesson' => $this->lessons->present($lesson, $user, null, $progress, false),
        ]);
    }

    /**
     * GET /lessons/{lesson}/download — stream the lesson's primary file.
     * ?inline=1 sets Content-Disposition: inline (for in-app viewers / video players).
     * View-only files (anything but xlsx/xls/csv/zip) are always inline; ?download=1 → 403.
     */
    public function download(Request $request, Lesson $lesson)
    {
        $this->lessons->authorize($request->user(), $lesson);

        $file = $this->lessons->primaryFile($lesson);
        abort_unless($file, 404, 'This lesson has no downloadable file.');

        return $this->lessons->fileResponse(
            $file['disk'], $file['path'], $file['name'], $file['mime'], $request->boolean('inline'),
            $file['downloadable'], $request->boolean('download')
        );
    }

    /**
     * GET /lessons/{lesson}/files/{file}/download — stream a specific learning file attached to the lesson.
     */
    public function downloadFile(Request $request, Lesson $lesson, LearningFile $file)
    {
        $this->lessons->authorize($request->user(), $lesson);
        abort_unless((int) $file->lesson_id === (int) $lesson->id, 404, 'File not found for this lesson.');

        return $this->lessons->streamFile($file, $request);
    }

    /**
     * PUT /lessons/{lesson}/progress — save progress / completion and/or add reading time.
     * "completed" is optional (omitted = unchanged); "time_spent_seconds_delta" (0..3600, clamped)
     * is added to the stored total. "client_operation_id" makes the call idempotent.
     */
    public function progress(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $this->lessons->authorize($user, $lesson);

        $data = $request->validate([
            'completed' => ['required_without_all:time_spent_seconds_delta,time_spent_seconds', 'nullable', 'boolean'],
            'time_spent_seconds_delta' => ['nullable', 'integer', 'min:0'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0'],
            'client_operation_id' => ['nullable', 'string', 'max:190'],
        ], [
            'completed.required_without_all' => 'The completed field is required.',
        ]);

        $operationId = $data['client_operation_id'] ?? null;

        if ($operationId) {
            $existing = DB::table('mobile_sync_operations')
                ->where('user_id', $user->id)
                ->where('client_operation_id', $operationId)
                ->first();

            if ($existing) {
                return response()->json(
                    ($existing->result ? json_decode($existing->result, true) : []) + ['duplicate' => true]
                );
            }
        }

        $result = $this->lessons->saveProgress(
            $user,
            $lesson,
            array_key_exists('completed', $data) && $data['completed'] !== null ? (bool) $data['completed'] : null,
            $this->lessons->secondsToAdd($data)
        );

        if ($operationId) {
            DB::table('mobile_sync_operations')->insertOrIgnore([
                'user_id' => $user->id,
                'client_operation_id' => $operationId,
                'operation_type' => 'lesson_progress',
                'payload' => json_encode(['lesson_id' => $lesson->id] + $data),
                'status' => 'processed',
                'result' => json_encode($result),
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json($result);
    }

    /**
     * GET /assignments/{assessment}/attachment — stream an assessment's first instructor attachment.
     */
    public function assessmentAttachment(Request $request, Assessment $assessment)
    {
        $this->authorizeAssessment($request, $assessment);

        $file = app(\App\Services\Learning\LearningFileService::class)->assessmentFiles($assessment)->first();
        abort_unless($file, 404, 'This assessment has no attachment.');

        return $this->lessons->streamFile($file, $request);
    }

    /**
     * GET /assignments/{assessment}/attachments/{file} — stream one of an assessment's attachments.
     */
    public function assessmentAttachmentFile(Request $request, Assessment $assessment, LearningFile $file)
    {
        $this->authorizeAssessment($request, $assessment);
        abort_unless((int) $file->assessment_id === (int) $assessment->id, 404, 'File not found for this assessment.');

        return $this->lessons->streamFile($file, $request);
    }

    /**
     * GET /submissions/files/{file} — a participant's own submitted file (always downloadable).
     */
    public function submissionFile(Request $request, AssessmentAttemptFile $file)
    {
        $file->loadMissing('attempt');
        abort_unless($file->attempt && (int) $file->attempt->user_id === (int) $request->user()->id, 404, 'File not found.');

        return $this->lessons->streamFile($file, $request, true);
    }

    private function authorizeAssessment(Request $request, Assessment $assessment): void
    {
        abort_unless($assessment->is_published, 404, 'Assessment not found.');
        abort_unless(
            $assessment->course_id && $this->lessons->isEnrolled($request->user(), (int) $assessment->course_id),
            403,
            'You are not enrolled in the course for this assessment.'
        );
    }
}
