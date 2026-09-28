<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorDashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.dashboard');
    }

    public function myCourses(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->isStaff()
            && $user->isActive()
            && ($user->isSuperAdmin() || $user->hasAnyRole(['instructor','trainer'])),
            403
        );

        $query = $user->instructedCourses()->withCount('enrolments');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $courses = $query->orderBy('title')->paginate(12)->withQueryString();

        $allCourses = $user->instructedCourses()->withCount('enrolments')->get();

        return view('instructor.dashboard', [
            'courses' => $courses,
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
