<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrolment;
use Illuminate\Http\Request;

class EnrolmentAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Enrolment::with(['course','user','cohort'])->latest();

        if ($courseId = $request->get('course_id')) {
            $query->where('course_id', $courseId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return view('admin.elearning.enrolments.index', [
            'enrolments' => $query->paginate(25)->withQueryString(),
            'courses' => Course::orderBy('title')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => ['required','exists:courses,id'],
            'user_id' => ['required','exists:users,id'],
            'cohort_id' => ['nullable','exists:cohorts,id'],
        ]);

        Enrolment::firstOrCreate(
            ['course_id' => $data['course_id'], 'user_id' => $data['user_id']],
            [
                'cohort_id' => $data['cohort_id'] ?? null,
                'status' => 'enrolled',
                'enrolled_at' => now(),
            ]
        );

        return back()->with('success', 'Learner enrolled.');
    }
}
