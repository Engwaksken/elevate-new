<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseTimeSlot;
use App\Services\TimetableAccessService;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    use ExportsTables;
    public function index(Request $request)
    {
        $query = CourseTimeSlot::with(['course.branches', 'creator'])->orderBy('starts_at');
        if ($request->filled('course_id')) $query->where('course_id', $request->integer('course_id'));
        if ($request->filled('branch_id')) $query->whereHas('course.branches', fn ($q) => $q->where('branches.id', $request->integer('branch_id')));
        if ($request->filled('from')) {
            $request->validate(['from' => ['date_format:Y-m-d']]);
            $query->where('starts_at', '>=', $request->input('from').' 00:00:00');
        }
        if ($request->filled('status')) {
            $request->validate(['status' => ['in:scheduled,cancelled']]);
            $query->where('status', $request->input('status'));
        }

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'Course Timetables', $query, [
                'Course' => 'course.title',
                'Branches' => fn ($s) => $s->course?->branches->pluck('name')->join(', '),
                'Session' => 'title',
                'Starts' => 'starts_at',
                'Ends' => 'ends_at',
                'Timezone' => 'timezone',
                'Venue' => 'venue',
                'Added by' => 'creator.name',
                'Status' => 'status',
                'Notes' => 'notes',
            ], null, ['course_id' => 'Course', 'branch_id' => 'Branch']);
        }

        return view('admin.elearning.timetable.index', [
            'slots' => $query->paginate(30)->withQueryString(),
            'courses' => Course::orderBy('title')->get(),
            'branches' => \App\Models\Branch::orderBy('name')->get(),
        ]);
    }

    public function manage(Request $request, Course $course, TimetableAccessService $access)
    {
        abort_unless($access->canManage($request->user(), $course), 403);

        return view('admin.elearning.timetable.manage', [
            'course' => $course,
            'timeSlots' => $course->timeSlots()->orderBy('starts_at')->paginate(20, ['*'], 'timetable_page'),
            'staffTimetable' => true,
        ]);
    }
}
