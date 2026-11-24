<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseTimeSlot;
use App\Services\CourseTimetableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseTimetableController extends Controller
{
    public function store(Request $request, Course $course, CourseTimetableService $service)
    {
        $this->authorise($request, $course);

        // The "Add time slots" modal sends several rows (date + times) sharing one set of details.
        if ($request->has('slots')) {
            $count = $this->storeMany($request, $course, $service);

            return $this->redirect($request, $course, $count === 1 ? 'Time slot added.' : $count.' time slots added.');
        }

        $service->save($course, $this->validated($request), $request->user());

        return $this->redirect($request, $course, 'Time slot added.');
    }

    /**
     * All rows are saved or none: a clash on any row (with an existing session or
     * another new row) rolls the whole batch back and points at that row.
     */
    private function storeMany(Request $request, Course $course, CourseTimetableService $service): int
    {
        $shared = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'timezone' => ['required', 'timezone', 'max:64'],
            'venue' => ['nullable', 'string', 'max:190'],
            'meeting_link' => ['nullable', 'url:http,https', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'slots' => ['required', 'array', 'min:1', 'max:50'],
            'slots.*.session_date' => ['required', 'date_format:Y-m-d'],
            'slots.*.start_time' => ['required', 'date_format:H:i'],
            'slots.*.end_time' => ['required', 'date_format:H:i'],
        ], [
            'slots.*.session_date.required' => 'Choose a date for every slot.',
            'slots.*.start_time.required' => 'Enter a start time for every slot.',
            'slots.*.end_time.required' => 'Enter an end time for every slot.',
        ]);

        $rows = array_values($shared['slots']);
        $details = collect($shared)->except('slots')->all() + ['status' => 'scheduled'];

        DB::transaction(function () use ($rows, $details, $course, $service, $request) {
            foreach ($rows as $index => $row) {
                try {
                    $service->save($course, $details + $row, $request->user());
                } catch (ValidationException $exception) {
                    $message = collect($exception->errors())->flatten()->first();

                    throw ValidationException::withMessages([
                        "slots.{$index}.start_time" => 'Slot '.($index + 1).' ('.$row['session_date'].'): '.$message,
                    ]);
                }
            }
        });

        return count($rows);
    }

    public function update(Request $request, Course $course, CourseTimeSlot $slot, CourseTimetableService $service)
    {
        $this->authorise($request, $course, $slot);
        $service->save($course, $this->validated($request), $request->user(), $slot);

        return $this->redirect($request, $course, 'Time slot updated.');
    }

    public function destroy(Request $request, Course $course, CourseTimeSlot $slot)
    {
        $this->authorise($request, $course, $slot);
        $slot->delete();

        return $this->redirect($request, $course, 'Time slot deleted.');
    }

    private function authorise(Request $request, Course $course, ?CourseTimeSlot $slot = null): void
    {
        abort_unless(app(\App\Services\TimetableAccessService::class)->canManage($request->user(), $course), 403);
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

    private function redirect(Request $request, Course $course, string $message)
    {
        if ($request->routeIs('admin.elearning.timetable.*')) {
            return redirect()->route('admin.elearning.timetable.manage', $course)->with('success', $message);
        }
        return redirect()->route('instructor.courses.manage', ['course' => $course, 'tab' => 'timetable'])
            ->with('success', $message);
    }
}
