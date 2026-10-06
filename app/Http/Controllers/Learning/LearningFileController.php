<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAttemptFile;
use App\Models\Enrolment;
use App\Models\LearningFile;
use App\Services\Learning\LearningFileService;
use App\Services\Learning\ModuleAccessService;
use Illuminate\Http\Request;

class LearningFileController extends Controller
{
    /**
     * Lesson / assignment material file.
     *
     * Course staff may download anything. Everyone else gets the participant
     * policy: spreadsheets/archives download, everything else is view-only
     * (served inline; ?download=1 is refused with 403).
     */
    public function download(Request $request, LearningFile $file, LearningFileService $files, ModuleAccessService $moduleAccess)
    {
        $user = $request->user();
        $isCourseStaff = $files->canManageCourse($user, $file->course_id ? (int) $file->course_id : null);

        if (! $isCourseStaff) {
            if ($file->course_id) {
                $allowed = Enrolment::where('course_id', $file->course_id)
                    ->where('user_id', $user->id)
                    ->exists();

                abort_unless($allowed || $user->isStaff(), 403);
            }

            if (! $user->isStaff()) {
                $this->ensureMaterialIsAvailable($file, $user, $moduleAccess);
            }
        }

        return $files->respond($request, $file, $isCourseStaff || $files->isDownloadable($file));
    }

    /**
     * A participant's submitted file: the owner and course staff may view and download it.
     */
    public function submissionFile(Request $request, AssessmentAttemptFile $file, LearningFileService $files)
    {
        $file->loadMissing('attempt.assessment');
        $attempt = $file->attempt;
        abort_unless($attempt && $attempt->assessment, 404);

        $user = $request->user();
        $isOwner = (int) $attempt->user_id === (int) $user->id;

        abort_unless($isOwner || $files->canManageCourse($user, (int) $attempt->assessment->course_id), 403);

        return $files->respond($request, $file, true);
    }

    /** Participants only see materials of published lessons/assessments in modules they can open. */
    private function ensureMaterialIsAvailable(LearningFile $file, $user, ModuleAccessService $moduleAccess): void
    {
        if ($file->lesson_id) {
            $lesson = $file->lesson()->with('module.course')->first();

            abort_unless($lesson && $lesson->is_published && $lesson->module?->is_published, 404);
            abort_unless($moduleAccess->canAccess($lesson->module, $user), 403, 'This module is locked.');
        }

        if ($file->assessment_id) {
            $assessment = $file->assessment()->first();

            abort_unless($assessment && $assessment->is_published, 404);
        }
    }
}
