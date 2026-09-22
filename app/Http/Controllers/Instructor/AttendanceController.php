<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\Enrolment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function create(Course $course)
    {
        $this->authoriseInstructor($course);

        return view('instructor.attendance-create', [
            'course' => $course,
            'learners' => Enrolment::with('user')->where('course_id', $course->id)->get(),
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $this->authoriseInstructor($course);

        $data = $request->validate([
            'title' => ['required','string','max:190'],
            'session_date' => ['required','date'],
            'starts_at' => ['nullable'],
            'ends_at' => ['nullable'],
            'venue' => ['nullable','string','max:190'],
            'attendance' => ['nullable','array'],
        ]);

        DB::transaction(function () use ($course, $data) {
            $session = AttendanceSession::create([
                'course_id' => $course->id,
                'title' => $data['title'],
                'session_date' => $data['session_date'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'venue' => $data['venue'] ?? null,
            ]);

            foreach ($data['attendance'] ?? [] as $userId => $status) {
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'user_id' => $userId,
                    'status' => $status,
                ]);
            }
        });

        return redirect()->route('instructor.dashboard')
            ->with('success', 'Attendance recorded.');
    }

    private function authoriseInstructor(Course $course): void
    {
        abort_unless(
            $course->instructors()->where('users.id', auth()->id())->exists()
            || auth()->user()->hasPermission('courses.edit'),
            403
        );
    }
}
