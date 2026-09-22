<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CertificateService;
use App\Services\CourseProgressService;

class LessonController extends Controller
{
    public function show(Lesson $lesson)
    {
        $lesson->load('module.course');
        $course = $lesson->module->course;

        abort_unless(
            $lesson->is_published && $lesson->module->is_published && $course->status === 'published',
            404
        );

        abort_unless(
            Enrolment::where('course_id', $course->id)
                ->where('user_id', auth()->id())
                ->exists(),
            403
        );

        $progress = LessonProgress::firstOrNew([
            'lesson_id' => $lesson->id,
            'user_id' => auth()->id(),
        ]);

        if (! $progress->first_opened_at) {
            $progress->first_opened_at = now();
        }

        $progress->last_opened_at = now();
        $progress->save();

        return view('learning.lessons.show', compact('lesson', 'course'));
    }

    public function complete(
        Lesson $lesson,
        CourseProgressService $progressService,
        CertificateService $certificateService
    ) {
        $lesson->load('module.course');
        $course = $lesson->module->course;

        abort_unless(
            Enrolment::where('course_id', $course->id)
                ->where('user_id', auth()->id())
                ->exists(),
            403
        );

        LessonProgress::updateOrCreate(
            ['lesson_id' => $lesson->id, 'user_id' => auth()->id()],
            [
                'first_opened_at' => now(),
                'last_opened_at' => now(),
                'completed_at' => now(),
            ]
        );

        $enrolment = $progressService->recalculate($course, auth()->user());
        $certificateService->issueIfEligible($course, auth()->user());

        return back()->with('success', "Lesson completed. Progress: {$enrolment->progress_percent}%");
    }
}
