<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrolment;
use Illuminate\Http\Request;

class CourseCatalogueController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::where('status', 'published')->withCount(['modules','enrolments']);

        if ($search = trim((string) $request->get('search'))) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }

        if ($mode = $request->get('delivery_mode')) {
            $query->where('delivery_mode', $mode);
        }

        return view('learning.courses.index', [
            'courses' => $query->latest()->paginate(12)->withQueryString(),
            'myCourseIds' => auth()->check()
                ? Enrolment::where('user_id', auth()->id())->pluck('course_id')
                : collect(),
        ]);
    }

    public function show(Course $course)
    {
        abort_unless($course->status === 'published', 404);

        $course->load([
            'modules' => fn ($q) => $q->where('is_published', true)->orderBy('position'),
            'modules.lessons' => fn ($q) => $q->where('is_published', true)->orderBy('position'),
            'assessments' => fn ($q) => $q->where('is_published', true),
        ]);

        $enrolment = auth()->check()
            ? Enrolment::where('course_id', $course->id)->where('user_id', auth()->id())->first()
            : null;

        return view('learning.courses.show', compact('course', 'enrolment'));
    }
}
