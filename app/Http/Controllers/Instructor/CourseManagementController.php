<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseManagementController extends Controller
{
    public function show(Request $request, Course $course)
    {
        $this->authorise($course);

        $course->load(['cohorts']);

        $moduleQuery = $course->modules()
            ->withCount('lessons');

        if ($request->filled('module_search')) {
            $term = trim((string) $request->get('module_search'));
            $moduleQuery->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $modules = $moduleQuery
            ->orderBy('position')
            ->paginate(12, ['*'], 'modules_page')
            ->withQueryString();

        $lessonQuery = Lesson::query()
            ->with('module')
            ->whereHas('module', fn ($q) => $q->where('course_id', $course->id));

        if ($request->filled('lesson_search')) {
            $term = trim((string) $request->get('lesson_search'));
            $lessonQuery->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%");
            });
        }

        if ($request->filled('module_id')) {
            $lessonQuery->where('course_module_id', (int) $request->get('module_id'));
        }

        if ($request->filled('lesson_type')) {
            $lessonQuery->where('content_type', $request->get('lesson_type'));
        }

        if ($request->filled('period')) {
            $this->applyPeriod($lessonQuery, (string) $request->get('period'));
        }

        $lessons = $lessonQuery
            ->latest('updated_at')
            ->paginate(12, ['*'], 'lessons_page')
            ->withQueryString();

        $assessmentQuery = $course->assessments()->withCount('questions');

        if ($request->filled('assessment_search')) {
            $term = trim((string) $request->get('assessment_search'));
            $assessmentQuery->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('instructions', 'like', "%{$term}%");
            });
        }

        if ($request->filled('assessment_type')) {
            $assessmentQuery->where('type', $request->get('assessment_type'));
        }

        if ($request->filled('assessment_period')) {
            $this->applyPeriod($assessmentQuery, (string) $request->get('assessment_period'));
        }

        $assessments = $assessmentQuery
            ->latest('updated_at')
            ->paginate(12, ['*'], 'assessments_page')
            ->withQueryString();

        $participants = $course->enrolments()
            ->with(['user', 'cohort'])
            ->when($request->filled('participant_search'), function ($query) use ($request) {
                $search = trim((string) $request->get('participant_search'));

                $query->whereHas('user', fn ($user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('cohort_id'), fn ($q) => $q->where('cohort_id', (int) $request->get('cohort_id')))
            ->when($request->filled('participant_status'), fn ($q) => $q->where('status', $request->get('participant_status')))
            ->when($request->filled('participant_period'), function ($q) use ($request) {
                $this->applyPeriod($q, (string) $request->get('participant_period'));
            })
            ->latest()
            ->paginate(20, ['*'], 'participants_page')
            ->withQueryString();

        return view('instructor.course-manage', [
            'course' => $course,
            'modules' => $modules,
            'lessons' => $lessons,
            'assessments' => $assessments,
            'participants' => $participants,
            'stats' => [
                'modules' => $course->modules()->count(),
                'lessons' => Lesson::whereHas('module', fn ($q) => $q->where('course_id', $course->id))->count(),
                'assessments' => $course->assessments()->count(),
                'participants' => $course->enrolments()->count(),
            ],
        ]);
    }

    public function storeModule(Request $request, Course $course)
    {
        $this->authorise($course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:1'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $course->modules()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'position' => $data['position'] ?? (($course->modules()->max('position') ?? 0) + 1),
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', 'Module added.');
    }

    public function destroyModule(Course $course, CourseModule $module)
    {
        $this->authorise($course);
        abort_unless((int) $module->course_id === (int) $course->id, 404);

        $module->delete();

        return back()->with('success', 'Module deleted.');
    }

    public function storeLesson(Request $request, Course $course, CourseModule $module)
    {
        $this->authorise($course);
        abort_unless((int) $module->course_id === (int) $course->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'content' => ['nullable', 'string'],
            'content_type' => ['required', 'in:text,video,file,link,mixed'],
            'video_url' => ['nullable', 'url'],
            'external_url' => ['nullable', 'url'],
            'resource_file' => ['nullable', 'file', 'max:51200', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,jpg,jpeg,png,webp,mp3,mp4,m4a'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer', 'min:1'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $filePath = null;

        if ($request->hasFile('resource_file')) {
            $filePath = $request->file('resource_file')
                ->store('courses/'.$course->id.'/lessons', 'public');
        }

        $module->lessons()->create([
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'content_type' => $data['content_type'],
            'video_url' => $data['video_url'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'file_path' => $filePath,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'position' => $data['position'] ?? (($module->lessons()->max('position') ?? 0) + 1),
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', 'Lesson added.');
    }

    public function destroyLesson(Course $course, CourseModule $module, Lesson $lesson)
    {
        $this->authorise($course);

        abort_unless(
            (int) $module->course_id === (int) $course->id
            && (int) $lesson->course_module_id === (int) $module->id,
            404
        );

        if ($lesson->file_path && Storage::disk('public')->exists($lesson->file_path)) {
            Storage::disk('public')->delete($lesson->file_path);
        }

        $lesson->delete();

        return back()->with('success', 'Lesson deleted.');
    }

    public function downloadLessonFile(Course $course, CourseModule $module, Lesson $lesson)
    {
        $this->authorise($course);

        abort_unless(
            (int) $module->course_id === (int) $course->id
            && (int) $lesson->course_module_id === (int) $module->id
            && $lesson->file_path
            && Storage::disk('public')->exists($lesson->file_path),
            404
        );

        return Storage::disk('public')->download($lesson->file_path);
    }

    public function storeAssessment(Request $request, Course $course)
    {
        $this->authorise($course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'type' => ['required', 'in:quiz,assignment,exam'],
            'instructions' => ['nullable', 'string'],
            'pass_mark' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'opens_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'assessment_file' => ['nullable', 'file', 'max:51200', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,jpg,jpeg,png,webp'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $attachmentPath = null;

        if ($request->hasFile('assessment_file')) {
            $attachmentPath = $request->file('assessment_file')
                ->store('courses/'.$course->id.'/assessments', 'public');
        }

        $course->assessments()->create([
            'title' => $data['title'],
            'type' => $data['type'],
            'instructions' => $data['instructions'] ?? null,
            'pass_mark' => $data['pass_mark'],
            'max_attempts' => $data['max_attempts'],
            'opens_at' => $data['opens_at'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'attachment_path' => $attachmentPath,
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', ucfirst($data['type']).' added.');
    }

    public function downloadAssessmentFile(Course $course, Assessment $assessment)
    {
        $this->authorise($course);

        abort_unless(
            (int) $assessment->course_id === (int) $course->id
            && $assessment->attachment_path
            && Storage::disk('public')->exists($assessment->attachment_path),
            404
        );

        return Storage::disk('public')->download($assessment->attachment_path);
    }

    public function addQuestion(Request $request, Course $course, Assessment $assessment)
    {
        $this->authorise($course);
        abort_unless((int) $assessment->course_id === (int) $course->id, 404);

        $data = $request->validate([
            'question_type' => ['required', 'in:multiple_choice,true_false,short_text,long_text'],
            'question_text' => ['required', 'string'],
            'marks' => ['required', 'numeric', 'min:0.1'],
            'options_text' => ['nullable', 'string'],
            'correct_value' => ['nullable', 'string'],
        ]);

        $options = null;

        if ($data['question_type'] === 'true_false') {
            $options = ['true' => 'True', 'false' => 'False'];
        } elseif ($data['question_type'] === 'multiple_choice') {
            $options = collect(preg_split('/\r\n|\r|\n/', (string) ($data['options_text'] ?? '')))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->mapWithKeys(fn ($value, $index) => [(string) $index => $value])
                ->all();

            if (count($options) < 2) {
                return back()
                    ->withErrors(['options_text' => 'Multiple-choice questions require at least two options.'])
                    ->withInput();
            }
        }

        $assessment->questions()->create([
            'question_type' => $data['question_type'],
            'question_text' => $data['question_text'],
            'options' => $options,
            'correct_answer' => filled($data['correct_value'] ?? null)
                ? ['value' => $data['correct_value']]
                : null,
            'marks' => $data['marks'],
            'position' => ($assessment->questions()->max('position') ?? 0) + 1,
        ]);

        return back()->with('success', 'Question added.');
    }

    public function destroyQuestion(Course $course, Assessment $assessment, AssessmentQuestion $question)
    {
        $this->authorise($course);

        abort_unless(
            (int) $assessment->course_id === (int) $course->id
            && (int) $question->assessment_id === (int) $assessment->id,
            404
        );

        $question->delete();

        return back()->with('success', 'Question deleted.');
    }

    public function updateParticipant(Request $request, Course $course, Enrolment $enrolment)
    {
        $this->authorise($course);
        abort_unless((int) $enrolment->course_id === (int) $course->id, 404);

        $enrolment->update($request->validate([
            'status' => ['required', 'in:enrolled,active,in_progress,completed,withdrawn,cancelled'],
            'progress_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]));

        return back()->with('success', 'Participant progress updated.');
    }

    private function applyPeriod($query, string $period): void
    {
        match ($period) {
            'today' => $query->whereDate('updated_at', today()),
            'week' => $query->where('updated_at', '>=', now()->startOfWeek()),
            'month' => $query->where('updated_at', '>=', now()->startOfMonth()),
            'quarter' => $query->where('updated_at', '>=', now()->firstOfQuarter()),
            'year' => $query->where('updated_at', '>=', now()->startOfYear()),
            default => null,
        };
    }

    private function authorise(Course $course): void
    {
        abort_unless(
            $course->instructors()->where('users.id', auth()->id())->exists()
            || auth()->user()?->isSuperAdmin(),
            403
        );
    }
}
