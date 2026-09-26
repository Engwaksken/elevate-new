<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;

class InstructorDashboardController extends Controller
{
    public function index()
    {
        $courses = auth()->user()
            ->instructedCourses()
            ->withCount('enrolments')
            ->orderBy('title')
            ->get();

        return view('instructor.dashboard', [
            'courses' => $courses,
            'stats' => [
                'courses' => $courses->count(),
                'learners' => (int) $courses->sum('enrolments_count'),
                'lead_courses' => $courses->filter(
                    fn ($course) => (bool) data_get($course,'pivot.is_lead',false)
                )->count(),
                'active_courses' => $courses->where('status','published')->count()
                    ?: $courses->where('status','active')->count(),
            ],
        ]);
    }
}
