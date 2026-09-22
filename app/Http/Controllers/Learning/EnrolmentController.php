<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrolment;

class EnrolmentController extends Controller
{
    public function store(Course $course)
    {
        abort_unless($course->status === 'published', 404);
        abort_unless($course->self_enrolment_enabled, 403);

        Enrolment::firstOrCreate(
            ['course_id' => $course->id, 'user_id' => auth()->id()],
            ['status' => 'enrolled', 'enrolled_at' => now()]
        );

        return redirect()->route('learning.my-courses')
            ->with('success', 'You have been enrolled successfully.');
    }

    public function myCourses()
    {
        return view('learning.my-courses', [
            'enrolments' => Enrolment::with('course')
                ->where('user_id', auth()->id())
                ->latest()
                ->paginate(12),
        ]);
    }
}
