<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Enrolment;
use App\Services\Learning\ModuleAccessService;
use App\Services\Participant\ParticipantAssignmentService;
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
            'modules.lessons'=>fn ($q) => $q->where('is_published', true)
                ->visibleToCohort($enrolment->cohort_id ? (int) $enrolment->cohort_id : null)
                ->orderBy('position'),
            'assessments'=>fn ($q) => $q->where('is_published', true),
        ]);
        // Whole-course announcements plus those for her own group.
        $announcements = $course->announcements()
            ->current()
            ->visibleToCohort($enrolment->cohort_id ? (int) $enrolment->cohort_id : null)
            ->latest('published_at')
            ->limit(5)
            ->get();
        $moduleAccess = $course->modules->mapWithKeys(fn ($module) => [$module->id=>[
            'accessible'=>$access->canAccess($module, $request->user()),
            'complete'=>$access->moduleComplete($module, $request->user()),
        ]]);
        // Attempts per assessment, so the list offers only what she can still
        // open: one with every attempt used is shown as completed, not linked
        // (opening it would be refused).
        $attempts = AssessmentAttempt::where('user_id', $request->user()->id)
            ->whereIn('assessment_id', $course->assessments->pluck('id'))
            ->selectRaw('assessment_id, count(*) as used, max(percentage) as best')
            ->groupBy('assessment_id')
            ->get()
            ->keyBy('assessment_id');
        // Effective due dates (approved extensions applied) and extension status,
        // matching what the mobile app shows.
        $assignments = app(ParticipantAssignmentService::class);
        $dueContext = $assignments->context($request->user(), $course->assessments->pluck('id'));
        $assessmentDue = $course->assessments->mapWithKeys(fn ($assessment) => [$assessment->id => [
            'due' => $assignments->effectiveDueAt($assessment, $dueContext),
            'extension' => $dueContext['latest'][$assessment->id] ?? null,
        ]]);
        return view('learning.course-dashboard', ['course'=>$course,'enrolment'=>$enrolment,'moduleAccess'=>$moduleAccess,'assessmentAttempts'=>$attempts,'assessmentDue'=>$assessmentDue,'announcements'=>$announcements,
            'canViewTimetable'=>true,'timetable'=>app(CourseTimetableService::class)->forCourse($course)]);
    }
}
