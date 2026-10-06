<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorDashboardController extends Controller
{
    use ExportsTables;
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.dashboard');
    }

    public function myCourses(Request $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->isStaff()
            && $user->isActive()
            && ($user->isSuperAdmin() || $user->hasAnyRole(['instructor','trainer'])),
            403
        );

        $query = $user->instructedCourses()
            ->with('cohorts')
            ->withCount('enrolments');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        if ($request->filled('course_id')) {
            $query->where('courses.id', (int) $request->get('course_id'));
        }

        if ($request->filled('cohort_id')) {
            $cohortId = (int) $request->get('cohort_id');

            $query->whereHas('cohorts', fn ($q) => $q->where('cohorts.id', $cohortId));
        }

        if ($request->filled('status')) {
            $query->where('courses.status', $request->get('status'));
        }

        if ($request->filled('period')) {
            $period = (string) $request->get('period');

            match ($period) {
                'today' => $query->whereDate('courses.updated_at', today()),
                'week' => $query->where('courses.updated_at', '>=', now()->startOfWeek()),
                'month' => $query->where('courses.updated_at', '>=', now()->startOfMonth()),
                'quarter' => $query->where('courses.updated_at', '>=', now()->firstOfQuarter()),
                'year' => $query->where('courses.updated_at', '>=', now()->startOfYear()),
                default => null,
            };
        }

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'My Courses', (clone $query)->orderBy('title'), [
                'Course' => 'title',
                'Code' => 'code',
                'Cohorts' => fn ($c) => $c->cohorts->pluck('name')->join(', '),
                'Learners' => 'enrolments_count',
                'Lead instructor' => fn ($c) => (bool) data_get($c, 'pivot.is_lead', false),
                'Status' => 'status',
                'Start date' => 'start_date',
                'End date' => 'end_date',
            ], null, ['course_id' => 'Course', 'cohort_id' => 'Cohort']);
        }

        $courses = $query
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $allCourses = $user->instructedCourses()
            ->withCount('enrolments')
            ->get();

        $courseOptions = $user->instructedCourses()
            ->with('cohorts')
            ->select('courses.id', 'courses.title')
            ->orderBy('title')
            ->get();

        $cohortOptions = $courseOptions
            ->flatMap(fn ($course) => $course->cohorts)
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('instructor.dashboard', [
            'courses' => $courses,
            'courseOptions' => $courseOptions,
            'cohortOptions' => $cohortOptions,
            'stats' => [
                'courses' => $allCourses->count(),
                'learners' => (int) $allCourses->sum('enrolments_count'),
                'lead_courses' => $allCourses->filter(
                    fn ($course) => (bool) data_get($course,'pivot.is_lead',false)
                )->count(),
                'active_courses' => $allCourses->whereIn('status',['published','active'])->count(),
            ],
        ]);
    }
}
