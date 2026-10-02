<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrolment;
use App\Services\Learning\ModuleAccessService;
use App\Services\CourseTimetableService;
use Illuminate\Http\Request;

class CourseLearningController extends Controller
{
    public function show(Request $request, Course $course, ModuleAccessService $access)
    {
        abort_unless($course->status === 'published', 404);
        $enrolment = Enrolment::where('course_id', $course->id)->where('user_id', $request->user()->id)->first();
        abort_unless($enrolment, 403, 'You are not enrolled in this course.');
        $course->load(['branches',
            'modules'=>fn ($q) => $q->where('is_published', true)->orderBy('position'),
            'modules.lessons'=>fn ($q) => $q->where('is_published', true)->orderBy('position'),
            'assessments'=>fn ($q) => $q->where('is_published', true),
        ]);
        $moduleAccess = $course->modules->mapWithKeys(fn ($module) => [$module->id=>[
            'accessible'=>$access->canAccess($module, $request->user()),
            'complete'=>$access->moduleComplete($module, $request->user()),
        ]]);
        return view('learning.course-dashboard', ['course'=>$course,'enrolment'=>$enrolment,'moduleAccess'=>$moduleAccess,
            'canViewTimetable'=>true,'timetable'=>app(CourseTimetableService::class)->forCourse($course)]);
    }
}
