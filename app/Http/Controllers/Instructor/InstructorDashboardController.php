<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;

class InstructorDashboardController extends Controller
{
    public function index()
    {
        return view('instructor.dashboard', [
            'courses' => auth()->user()->instructedCourses()
                ->withCount('enrolments')
                ->orderBy('title')
                ->get(),
        ]);
    }
}
