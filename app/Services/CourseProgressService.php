<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\LessonProgress;
use App\Models\User;

class CourseProgressService
{
    public function recalculate(Course $course, User $user): Enrolment
    {
        $enrolment = Enrolment::firstOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            ['status' => 'enrolled', 'enrolled_at' => now()]
        );

        $lessonIds = $course->modules()
            ->with('lessons:id,course_module_id,is_published')
            ->get()
            ->flatMap(fn ($module) => $module->lessons->where('is_published', true)->pluck('id'));

        $total = $lessonIds->count();

        $completed = $total === 0 ? 0 : LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->count();

        $progress = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        $updates = ['progress_percent' => $progress];

        if ($progress > 0 && $enrolment->status === 'enrolled') {
            $updates['status'] = 'in_progress';
            $updates['started_at'] = $enrolment->started_at ?: now();
        }

        $enrolment->update($updates);

        return $enrolment->fresh();
    }
}
