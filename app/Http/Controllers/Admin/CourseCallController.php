<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\CourseApplication;
use App\Models\CourseCall;
use App\Models\Enrolment;
use App\Models\Programme;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CourseCallController extends Controller
{
    public function index(Request $request)
    {
        $q = CourseCall::with(['courses', 'cohort'])
            ->withCount('applications')
            ->latest();

        if ($search = trim((string) $request->get('search'))) {
            $q->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('courses', function ($courseQuery) use ($search) {
                        $courseQuery->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        return view('admin.course-calls.index', [
            'calls' => $q->paginate(20)->withQueryString(),

            /*
             * Only published/active learning courses may be attached to a new
             * Course Call.
             */
            'courses' => Course::query()
                ->where('status', 'published')
                ->orderBy('title')
                ->get(),

            'cohorts' => Cohort::orderBy('name')->get(),
            'programmes' => Programme::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'assessments' => Assessment::orderBy('title')->get(),
        ]);
    }

    public function show(CourseCall $courseCall)
    {
        $courseCall->load([
            'courses',
            'cohort',
            'programme',
            'project',
            'entryAssessment',
            'questions',
        ])->loadCount('applications');

        return view('admin.course-calls.show', compact('courseCall'));
    }

    public function edit(CourseCall $courseCall)
    {
        $courseCall->load('courses');

        return view('admin.course-calls.edit', [
            'courseCall' => $courseCall,
            'courses' => Course::query()
                ->where('status', 'published')
                ->orWhereIn('id', $courseCall->courses->pluck('id'))
                ->orderBy('title')
                ->get(),
            'cohorts' => Cohort::orderBy('name')->get(),
            'programmes' => Programme::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'assessments' => Assessment::orderBy('title')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $d = $this->validatedCall($request);

        $courseIds = collect($d['course_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        unset($d['course_ids']);

        $this->validateCoursesArePublished($courseIds);

        $d['created_by'] = auth()->id();

        /*
         * Keep course_id populated with the first selected course for legacy
         * code that has not yet been converted to the pivot relation.
         */
        $d['course_id'] = $courseIds[0] ?? null;

        $courseCall = DB::transaction(function () use ($d, $courseIds) {
            $courseCall = CourseCall::create($d);
            $courseCall->courses()->sync($courseIds);

            return $courseCall;
        });

        return redirect()
            ->route('admin.course-calls.show', $courseCall)
            ->with('success', 'Course call created.');
    }

    public function update(Request $request, CourseCall $courseCall)
    {
        $d = $this->validatedCall($request);

        $courseIds = collect($d['course_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        unset($d['course_ids']);

        $this->validateCoursesArePublished($courseIds);

        $d['course_id'] = $courseIds[0] ?? null;

        DB::transaction(function () use ($courseCall, $d, $courseIds): void {
            $courseCall->update($d);
            $courseCall->courses()->sync($courseIds);
        });

        return redirect()
            ->route('admin.course-calls.show', $courseCall)
            ->with('success', 'Course call updated.');
    }

    public function addQuestion(Request $request, CourseCall $courseCall)
    {
        $d = $request->validate([
            'question_type' => 'required|in:short_text,long_text,single_choice,multiple_choice,yes_no,number,date',
            'question_text' => 'required|string',
            'options_text' => 'nullable|string',
            'is_required' => 'nullable|boolean',
        ]);

        $options = $d['options_text']
            ? collect(preg_split('/\r\n|\r|\n/', $d['options_text']))
                ->map(fn ($value) => trim($value))
                ->filter()
                ->values()
                ->all()
            : null;

        $courseCall->questions()->create([
            'question_type' => $d['question_type'],
            'question_text' => $d['question_text'],
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'position' => $courseCall->questions()->max('position') + 1,
        ]);

        return back()->with('success', 'Application question added.');
    }

    public function destroy(CourseCall $courseCall)
    {
        abort_if(
            $courseCall->applications()->exists(),
            422,
            'A course call with applications cannot be deleted.'
        );

        $courseCall->delete();

        return redirect()
            ->route('admin.course-calls.index')
            ->with('success', 'Course call deleted.');
    }

    public function applications(CourseCall $courseCall)
    {
        /*
         * A general Course Call can contain several courses. Staff with the
         * permission may review it. Instructor course-specific restrictions
         * continue to be enforced at the individual approval/review stage.
         */
        abort_unless(
            auth()->user()->isSuperAdmin()
            || auth()->user()->hasPermission('applications.review'),
            403
        );

        return view('admin.course-applications.index', [
            'courseCall' => $courseCall->load([
                'courses',
                'entryAssessment',
            ]),
            'applications' => $courseCall->applications()
                ->with(['user', 'assessmentAttempt'])
                ->latest()
                ->paginate(30),
        ]);
    }

    public function review(Request $request, CourseApplication $application)
    {
        $application->load('courseCall.courses');

        $call = $application->courseCall;

        abort_unless(
            auth()->user()->isSuperAdmin()
            || auth()->user()->hasPermission('applications.review'),
            403
        );

        $d = $request->validate([
            'status' => 'required|in:submitted,shortlisted,approved,waitlisted,rejected',
            'application_score' => 'nullable|numeric|min:0|max:100',
            'reviewer_comments' => 'nullable|string|max:5000',
            /*
             * Because the call may contain multiple courses, approval requires
             * selecting the exact course the participant is being enrolled in.
             */
            'approved_course_id' => 'nullable|integer|exists:courses,id',
        ]);

        $approvedCourseId = isset($d['approved_course_id'])
            ? (int) $d['approved_course_id']
            : null;

        if ($d['status'] === 'approved') {
            if (! $approvedCourseId) {
                throw ValidationException::withMessages([
                    'approved_course_id' => 'Select the course this participant is being approved for.',
                ]);
            }

            if (! $call->courses->contains('id', $approvedCourseId)) {
                throw ValidationException::withMessages([
                    'approved_course_id' => 'The selected course is not part of this Course Call.',
                ]);
            }

            if (auth()->user()->hasRole('instructor')) {
                $assigned = DB::table('course_instructors')
                    ->where('course_id', $approvedCourseId)
                    ->where('user_id', auth()->id())
                    ->exists();

                abort_unless(
                    $assigned,
                    403,
                    'You may approve participants only into courses assigned to you.'
                );
            }
        }

        $attempt = null;

        if ($call->entry_assessment_id) {
            $attempt = AssessmentAttempt::query()
                ->where('assessment_id', $call->entry_assessment_id)
                ->where('user_id', $application->user_id)
                ->whereIn('status', ['submitted', 'graded'])
                ->latest('attempt_number')
                ->first();

            if ($attempt) {
                $application->entry_assessment_score = $attempt->percentage;
                $application->assessment_attempt_id = $attempt->id;
            }

            if ($d['status'] === 'approved') {
                if (! $attempt) {
                    throw ValidationException::withMessages([
                        'status' => 'The participant must complete the linked entry assessment before approval/enrolment.',
                    ]);
                }

                if ($attempt->status !== 'graded') {
                    throw ValidationException::withMessages([
                        'status' => 'The linked entry assessment must be graded before approval/enrolment.',
                    ]);
                }
            }
        }

        unset($d['approved_course_id']);

        $application->fill($d);
        $application->reviewed_by = auth()->id();
        $application->reviewed_at = now();
        $application->save();

        if ($d['status'] === 'approved') {
            if (
                $call->available_slots !== null
                && $call->applications()
                    ->where('status', 'approved')
                    ->whereNotNull('enrolled_at')
                    ->whereKeyNot($application->id)
                    ->count() >= $call->available_slots
            ) {
                throw ValidationException::withMessages([
                    'status' => 'This Course Call has reached its available enrolment slots.',
                ]);
            }

            $enrolment = Enrolment::firstOrCreate(
                [
                    'course_id' => $approvedCourseId,
                    'user_id' => $application->user_id,
                ],
                [
                    'cohort_id' => $call->cohort_id,
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                    'progress_percent' => 0,
                ]
            );

            if (Schema::hasColumn('enrolments', 'source_type')) {
                $enrolment->update([
                    'source_type' => 'application',
                    'source_id' => $application->id,
                ]);
            }

            $application->update(['enrolled_at' => now()]);
        }

        return back()->with('success', 'Application review saved.');
    }

    private function validatedCall(Request $request): array
    {
        return $request->validate([
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],

            'programme_id' => ['nullable', 'exists:programmes,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'cohort_id' => ['nullable', 'exists:cohorts,id'],

            /*
             * A general multi-course Course Call does not use one global entry
             * assessment unless WITU intentionally selects one. It remains
             * optional for backwards compatibility.
             */
            'entry_assessment_id' => ['nullable', 'exists:assessments,id'],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'eligibility_criteria' => ['nullable', 'string'],
            'available_slots' => ['nullable', 'integer', 'min:1'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'status' => ['required', 'in:draft,published,closed,archived'],
        ]);
    }

    private function validateCoursesArePublished(array $courseIds): void
    {
        $publishedCount = Course::query()
            ->whereIn('id', $courseIds)
            ->where('status', 'published')
            ->count();

        if ($publishedCount !== count($courseIds)) {
            throw ValidationException::withMessages([
                'course_ids' => 'Only active/published courses can be included in a Course Call.',
            ]);
        }
    }
}
