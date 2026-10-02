<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseTimeSlot;
use App\Services\CourseTimetableService;
use Illuminate\Http\Request;

class CourseTimetableController extends Controller
{
    public function store(Request $request, Course $course, CourseTimetableService $service)
    {
        $this->authorise($request, $course);
        $service->save($course, $this->validated($request), $request->user());

        return $this->redirect($course, 'Time slot added.');
    }

    public function update(Request $request, Course $course, CourseTimeSlot $slot, CourseTimetableService $service)
    {
        $this->authorise($request, $course, $slot);
        $service->save($course, $this->validated($request), $request->user(), $slot);

        return $this->redirect($course, 'Time slot updated.');
    }

    public function destroy(Request $request, Course $course, CourseTimeSlot $slot)
    {
        $this->authorise($request, $course, $slot);
        $slot->delete();

        return $this->redirect($course, 'Time slot deleted.');
    }

    private function authorise(Request $request, Course $course, ?CourseTimeSlot $slot = null): void
    {
        $user = $request->user();
        abort_unless($user && $user->isStaff() && $user->isActive()
            && ($user->isSuperAdmin() || ($user->hasAnyRole(['instructor', 'trainer'])
                && $user->instructedCourses()->whereKey($course->id)->exists())), 403);
        if ($slot) {
            abort_unless($slot->course_id === $course->id, 404);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'session_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['required', 'timezone', 'max:64'],
            'venue' => ['nullable', 'string', 'max:190'],
            'meeting_link' => ['nullable', 'url:http,https', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'in:scheduled,cancelled'],
        ]);
    }

    private function redirect(Course $course, string $message)
    {
        return redirect()->route('instructor.courses.manage', ['course' => $course, 'tab' => 'timetable'])
            ->with('success', $message);
    }
}
