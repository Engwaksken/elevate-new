<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\LearningFile;
use App\Models\Lesson;
use App\Services\Participant\ParticipantLessonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
     */
    public function download(Request $request, Lesson $lesson)
    {
        $this->lessons->authorize($request->user(), $lesson);

        $file = $this->lessons->primaryFile($lesson);
        abort_unless($file, 404, 'This lesson has no downloadable file.');

        return $this->lessons->fileResponse(
            $file['disk'], $file['path'], $file['name'], $file['mime'], $request->boolean('inline')
        );
    }

    /**
     * GET /lessons/{lesson}/files/{file}/download — stream a specific learning file attached to the lesson.
     */
    public function downloadFile(Request $request, Lesson $lesson, LearningFile $file)
    {
        $this->lessons->authorize($request->user(), $lesson);
        abort_unless((int) $file->lesson_id === (int) $lesson->id, 404, 'File not found for this lesson.');

        return $this->lessons->fileResponse(
            $file->disk ?: 'local',
            (string) $file->path,
            $file->original_name ?: $file->stored_name,
            $file->mime_type,
            $request->boolean('inline')
        );
    }

    /**
     * PUT /lessons/{lesson}/progress — save progress / completion.
     */
    public function progress(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $this->lessons->authorize($user, $lesson);

        $data = $request->validate([
            'completed' => ['required', 'boolean'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json(
            $this->lessons->saveProgress($user, $lesson, (bool) $data['completed'], (int) ($data['time_spent_seconds'] ?? 0))
        );
    }

    /**
     * GET /assignments/{assessment}/attachment — stream an assessment's instructor attachment.
     */
    public function assessmentAttachment(Request $request, Assessment $assessment)
    {
        abort_unless($assessment->is_published, 404, 'Assessment not found.');
        abort_unless(
            $assessment->course_id && $this->lessons->isEnrolled($request->user(), (int) $assessment->course_id),
            403,
            'You are not enrolled in the course for this assessment.'
        );
        abort_unless(
            $assessment->attachment_path && Storage::disk('public')->exists($assessment->attachment_path),
            404,
            'This assessment has no attachment.'
        );

        return $this->lessons->fileResponse(
            'public',
            $assessment->attachment_path,
            basename($assessment->attachment_path),
            null,
            $request->boolean('inline')
        );
    }
}
