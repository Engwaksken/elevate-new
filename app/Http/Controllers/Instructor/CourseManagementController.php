<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssignmentExtensionRequest;
use App\Models\Course;
use App\Models\CourseAnnouncement;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\LearningFile;
use App\Models\Lesson;
use App\Services\Files\FilePreviewService;
use App\Services\Learning\LearningFileService;
use App\Services\Participant\ParticipantAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseManagementController extends Controller
{
    use ExportsTables;
    public function show(Request $request, Course $course)
    {
        $this->authorise($course);

        $course->load(['cohorts']);

        $moduleQuery = $course->modules()->withCount('lessons');

        if ($request->filled('module_search')) {
            $term = trim((string) $request->get('module_search'));
            $moduleQuery->where(function ($query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $modules = $moduleQuery
            ->orderBy('position')
            ->paginate(20, ['*'], 'modules_page')
            ->withQueryString();

        $this->syncLegacyFiles($course);

        $lessonQuery = Lesson::query()
            ->with(['module', 'files'])
            ->whereHas('module', fn ($query) => $query->where('course_id', $course->id));

        if ($request->filled('lesson_search')) {
            $term = trim((string) $request->get('lesson_search'));
            $lessonQuery->where(function ($query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%");
            });
        }

        if ($request->filled('module_id')) {
            $lessonQuery->where('course_module_id', (int) $request->get('module_id'));
        }

        if ($request->filled('lesson_type')) {
            $lessonQuery->where('content_type', (string) $request->get('lesson_type'));
        }

        $lessons = $lessonQuery
            ->orderBy('course_module_id')
            ->orderBy('position')
            ->paginate(24, ['*'], 'lessons_page')
            ->withQueryString();

        $assessmentQuery = $course->assessments()
            ->with('files')
            ->withCount(['questions', 'attempts']);

        if ($request->filled('assessment_search')) {
            $term = trim((string) $request->get('assessment_search'));
            $assessmentQuery->where(function ($query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('instructions', 'like', "%{$term}%");
            });
        }

        if ($request->filled('assessment_type')) {
            $assessmentQuery->where('type', (string) $request->get('assessment_type'));
        }

        $assessments = $assessmentQuery
            ->latest('updated_at')
            ->paginate(24, ['*'], 'assessments_page')
            ->withQueryString();

        $participantQuery = $course->enrolments()
            ->with(['user', 'cohort'])
            ->when($request->filled('participant_search'), function ($query) use ($request) {
                $search = trim((string) $request->get('participant_search'));

                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where(function ($inner) use ($search) {
                        $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('participant_code', 'like', "%{$search}%");
                    });
                });
            })
            ->when(
                $request->filled('participant_status'),
                fn ($query) => $query->where('status', (string) $request->get('participant_status'))
            )
            ->when(
                $request->filled('cohort_id'),
                fn ($query) => $query->where('cohort_id', (int) $request->get('cohort_id'))
            )
            ->latest();

        $submissionQuery = AssessmentAttempt::query()
            ->with(['assessment', 'user', 'files'])
            ->whereHas('assessment', fn ($query) => $query->where('course_id', $course->id))
            ->when($request->filled('submission_search'), function ($query) use ($request) {
                $term = trim((string) $request->get('submission_search'));

                $query->where(function ($inner) use ($term) {
                    $inner->whereHas('user', function ($userQuery) use ($term) {
                        $userQuery->where(function ($userInner) use ($term) {
                            $userInner->where('name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%");
                        });
                    })->orWhereHas(
                        'assessment',
                        fn ($assessment) => $assessment->where('title', 'like', "%{$term}%")
                    );
                });
            })
            ->when(
                $request->filled('submission_status'),
                fn ($query) => $query->where('status', (string) $request->get('submission_status'))
            )
            ->latest('submitted_at');

        if ($format = $this->exportFormat($request)) {
            if ($request->query('list') === 'submissions') {
                return $this->exportTable($format, 'Submissions - '.$course->title, $submissionQuery, [
                    'Participant' => 'user.name',
                    'Email' => 'user.email',
                    'Assessment' => 'assessment.title',
                    'Type' => 'assessment.type',
                    'Attempt' => 'attempt_number',
                    'Submitted' => 'submitted_at',
                    'Score' => 'score',
                    'Percentage' => 'percentage',
                    'Status' => 'status',
                    'Feedback' => 'instructor_feedback',
                    'Files' => fn ($attempt) => $attempt->files->pluck('original_name')->join(', '),
                ], [
                    'Search' => $request->get('submission_search'),
                    'Status' => $request->get('submission_status'),
                ]);
            }

            return $this->exportTable($format, 'Participants - '.$course->title, $participantQuery, [
                'Participant' => 'user.name',
                'Participant code' => 'user.participant_code',
                'Email' => 'user.email',
                'Cohort' => 'cohort.name',
                'Status' => fn ($e) => ucfirst(str_replace('_', ' ', (string) $e->status)),
                'Progress (%)' => fn ($e) => number_format((float) $e->progress_percent, 1),
                'Final score (%)' => 'final_score',
                'Enrolled' => 'enrolled_at',
                'Completed' => 'completed_at',
            ], [
                'Search' => $request->get('participant_search'),
                'Status' => $request->get('participant_status'),
                'Cohort' => $request->filled('cohort_id') ? $course->cohorts->firstWhere('id', (int) $request->get('cohort_id'))?->name : null,
            ]);
        }

        $participants = $participantQuery
            ->paginate(20, ['*'], 'participants_page')
            ->withQueryString();

        $submissions = $submissionQuery
            ->paginate(20, ['*'], 'submissions_page')
            ->withQueryString();

        $extensionRequests = AssignmentExtensionRequest::query()
            ->with(['assessment', 'user', 'reviewer'])
            ->whereHas('assessment', fn ($query) => $query->where('course_id', $course->id))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(20, ['*'], 'extensions_page')
            ->withQueryString();

        $pendingExtensionCount = AssignmentExtensionRequest::query()
            ->where('status', AssignmentExtensionRequest::STATUS_PENDING)
            ->whereHas('assessment', fn ($query) => $query->where('course_id', $course->id))
            ->count();

        $announcements = $course->announcements()
            ->with('creator')
            ->latest('published_at')
            ->paginate(20, ['*'], 'announcements_page')
            ->withQueryString();

        $progressRows = $this->progressRows($course, $participants->getCollection());

        return view('instructor.course-manage', [
            'course' => $course,
            'modules' => $modules,
            'lessons' => $lessons,
            'assessments' => $assessments,
            'participants' => $participants,
            'participantHistory' => app(\App\Services\ParticipantHistoryService::class)
                ->summaries($participants->getCollection()->pluck('user_id'), $course->id),
            'submissions' => $submissions,
            'announcements' => $announcements,
            'timeSlots' => $course->timeSlots()->orderBy('starts_at')->paginate(20, ['*'], 'timetable_page')->withQueryString(),
            'extensionRequests' => $extensionRequests,
            'pendingExtensionCount' => $pendingExtensionCount,
            'progressRows' => $progressRows,
            'stats' => [
                'modules' => $course->modules()->count(),
                'lessons' => Lesson::whereHas(
                    'module',
                    fn ($query) => $query->where('course_id', $course->id)
                )->count(),
                'assignments' => $course->assessments()->where('type', 'assignment')->count(),
                'quizzes' => $course->assessments()->where('type', 'quiz')->count(),
                'exams' => $course->assessments()->where('type', 'exam')->count(),
                'participants' => $course->enrolments()->count(),
            ],
        ]);
    }

    public function updateCourse(Request $request, Course $course)
    {
        $this->authorise($course);

        $course->update($request->validate([
            'title' => ['required', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'delivery_mode' => ['required', 'in:online,in_person,blended'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'duration_hours' => ['nullable', 'integer', 'min:1'],
            'pass_mark' => ['required', 'numeric', 'min:0', 'max:100'],
        ]));

        return back()->with('success', 'Course information updated.');
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

    public function updateModule(Request $request, Course $course, CourseModule $module)
    {
        $this->authoriseModule($course, $module);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'position' => ['required', 'integer', 'min:1'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $module->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'position' => $data['position'],
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', 'Module updated.');
    }

    public function destroyModule(Course $course, CourseModule $module)
    {
        $this->authoriseModule($course, $module);
        $module->delete();

        return back()->with('success', 'Module deleted.');
    }

    public function storeLesson(Request $request, Course $course, CourseModule $module)
    {
        $this->authoriseModule($course, $module);

        $data = $this->validateLesson($request);

        $lesson = $module->lessons()->create([
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'content_type' => $data['content_type'],
            'video_url' => $data['video_url'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'position' => $data['position'] ?? (($module->lessons()->max('position') ?? 0) + 1),
            'is_published' => $request->boolean('is_published'),
        ]);

        $stored = $this->files()->storeMaterials(
            $this->files()->uploadedFiles($request, 'resource_files', 'resource_file'),
            $course->id,
            $lesson,
            null,
            $request->user()
        );

        return back()->with('success', 'Lesson added'.($stored->isNotEmpty() ? ' with '.$stored->count().' file(s).' : '.'));
    }

    public function updateLesson(
        Request $request,
        Course $course,
        CourseModule $module,
        Lesson $lesson
    ) {
        $this->authoriseLesson($course, $module, $lesson);

        $data = $this->validateLesson($request);

        // Legacy "remove existing file" checkbox: removes the original single file.
        if ($request->boolean('remove_file') && $lesson->file_path) {
            $this->files()->syncLesson($lesson);
            $legacyPath = (string) $this->files()->normalisePath($lesson->file_path);
            $lesson->files()->where('path', $legacyPath)->get()
                ->each(fn (LearningFile $file) => $this->files()->deleteLearningFile($file));
            if ($legacyPath !== '') {
                Storage::disk('public')->delete($legacyPath);
            }
            $lesson->forceFill(['file_path' => null])->save();
        }

        // Existing files ticked for removal in the edit form.
        $this->removeSelectedFiles($request, $lesson->files());

        $this->files()->storeMaterials(
            $this->files()->uploadedFiles($request, 'resource_files', 'resource_file'),
            $course->id,
            $lesson,
            null,
            $request->user()
        );

        $lesson->update([
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'content_type' => $data['content_type'],
            'video_url' => $data['video_url'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'position' => $data['position'] ?? $lesson->position,
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', 'Lesson updated.');
    }

    public function destroyLesson(Course $course, CourseModule $module, Lesson $lesson)
    {
        $this->authoriseLesson($course, $module, $lesson);

        $this->files()->deleteAllFor($lesson);

        if ($path = $this->files()->normalisePath($lesson->file_path)) {
            Storage::disk('public')->delete($path);
        }

        $lesson->delete();

        return back()->with('success', 'Lesson deleted.');
    }

    public function downloadLessonFile(Course $course, CourseModule $module, Lesson $lesson)
    {
        $this->authoriseLesson($course, $module, $lesson);

        // Legacy single-file route: serves the lesson's first file (staff keep full download).
        $file = $this->files()->lessonFiles($lesson)->first();
        abort_unless($file, 404);

        return $this->files()->respond(request(), $file, true);
    }

    public function storeAssessment(Request $request, Course $course)
    {
        $this->authorise($course);

        $data = $this->validateAssessment($request);

        $assessment = $course->assessments()->create([
            'course_module_id' => $data['course_module_id'] ?? null,
            'title' => $data['title'],
            'type' => $data['type'],
            'instructions' => $data['instructions'] ?? null,
            'pass_mark' => $data['pass_mark'],
            'max_attempts' => $data['max_attempts'],
            'opens_at' => $data['opens_at'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'total_marks' => $data['total_marks'] ?? null,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->files()->storeMaterials(
            $this->files()->uploadedFiles($request, 'assessment_files', 'assessment_file'),
            $course->id,
            null,
            $assessment,
            $request->user()
        );

        return back()->with('success', ucfirst($data['type']).' added.');
    }

    public function updateAssessment(Request $request, Course $course, Assessment $assessment)
    {
        $this->authoriseAssessment($course, $assessment);

        $data = $this->validateAssessment($request);

        // Legacy "remove attachment" checkbox: removes the original single attachment.
        if ($request->boolean('remove_attachment') && $assessment->attachment_path) {
            $this->files()->syncAssessments([$assessment]);
            $legacyPath = (string) $this->files()->normalisePath($assessment->attachment_path);
            $assessment->files()->where('path', $legacyPath)->get()
                ->each(fn (LearningFile $file) => $this->files()->deleteLearningFile($file));
            if ($legacyPath !== '') {
                Storage::disk('public')->delete($legacyPath);
            }
            $assessment->forceFill(['attachment_path' => null])->save();
        }

        $this->removeSelectedFiles($request, $assessment->files());

        $this->files()->storeMaterials(
            $this->files()->uploadedFiles($request, 'assessment_files', 'assessment_file'),
            $course->id,
            null,
            $assessment,
            $request->user()
        );

        $assessment->update([
            'course_module_id' => $data['course_module_id'] ?? null,
            'title' => $data['title'],
            'type' => $data['type'],
            'instructions' => $data['instructions'] ?? null,
            'pass_mark' => $data['pass_mark'],
            'max_attempts' => $data['max_attempts'],
            'opens_at' => $data['opens_at'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'total_marks' => $data['total_marks'] ?? null,
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', ucfirst($data['type']).' updated.');
    }

    public function destroyAssessment(Course $course, Assessment $assessment)
    {
        $this->authoriseAssessment($course, $assessment);

        $this->files()->deleteAllFor($assessment);

        if ($path = $this->files()->normalisePath($assessment->attachment_path)) {
            Storage::disk('public')->delete($path);
        }

        $assessment->delete();

        return back()->with('success', 'Assessment deleted.');
    }

    public function downloadAssessmentFile(Course $course, Assessment $assessment)
    {
        $this->authoriseAssessment($course, $assessment);

        // Legacy single-file route: serves the assessment's first attachment.
        $file = $this->files()->assessmentFiles($assessment)->first();
        abort_unless($file, 404);

        return $this->files()->respond(request(), $file, true);
    }

    /**
     * Remove one lesson or assessment material file.
     */
    public function destroyFile(Course $course, LearningFile $file)
    {
        $this->authorise($course);

        $belongsToCourse = (int) $file->course_id === (int) $course->id
            || ($file->lesson_id && Lesson::whereKey($file->lesson_id)->whereHas('module', fn ($q) => $q->where('course_id', $course->id))->exists())
            || ($file->assessment_id && Assessment::whereKey($file->assessment_id)->where('course_id', $course->id)->exists());

        abort_unless($belongsToCourse, 404);

        $name = $file->displayName();
        $this->files()->deleteLearningFile($file);

        return back()->with('success', 'File "'.$name.'" removed.');
    }

    public function addQuestion(Request $request, Course $course, Assessment $assessment)
    {
        $this->authoriseAssessment($course, $assessment);

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
            $values = collect(preg_split('/\r\n|\r|\n/', (string) ($data['options_text'] ?? '')))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values();

            if ($values->count() < 2) {
                return back()
                    ->withErrors(['options_text' => 'Multiple-choice questions require at least two options.'])
                    ->withInput();
            }

            $options = $values
                ->mapWithKeys(fn ($value, $index) => [(string) $index => $value])
                ->all();
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

    public function destroyQuestion(
        Course $course,
        Assessment $assessment,
        AssessmentQuestion $question
    ) {
        $this->authoriseAssessment($course, $assessment);

        abort_unless((int) $question->assessment_id === (int) $assessment->id, 404);

        $question->delete();

        return back()->with('success', 'Question deleted.');
    }

    public function storeAnnouncement(Request $request, Course $course)
    {
        $this->authorise($course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ]);

        $course->announcements()->create([
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => $data['published_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Announcement posted.');
    }

    public function destroyAnnouncement(Course $course, CourseAnnouncement $announcement)
    {
        $this->authorise($course);
        abort_unless((int) $announcement->course_id === (int) $course->id, 404);

        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    public function reviewSubmission(Request $request, Course $course, AssessmentAttempt $attempt)
    {
        $this->authorise($course);

        $attempt->loadMissing('assessment');

        abort_unless(
            (int) $attempt->assessment?->course_id === (int) $course->id,
            404
        );

        $data = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'instructor_feedback' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:submitted,graded'],
        ]);

        $attempt->update([
            'score' => $data['score'] ?? null,
            'percentage' => $data['percentage'] ?? null,
            'instructor_feedback' => $data['instructor_feedback'] ?? null,
            'status' => $data['status'],
            'graded_at' => $data['status'] === 'graded' ? now() : null,
            'graded_by' => $data['status'] === 'graded' ? auth()->id() : null,
        ]);

        return back()->with('success', 'Submission review saved.');
    }

    /**
     * Approve a participant's assignment extension request with a new (future) due date.
     */
    public function approveExtension(
        Request $request,
        Course $course,
        AssignmentExtensionRequest $extensionRequest,
        ParticipantAssignmentService $assignments
    ) {
        $this->authoriseExtension($course, $extensionRequest);

        $data = $request->validate([
            'approved_due_at' => ['required', 'date', 'after:now'],
            'reviewer_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'approved_due_at.after' => 'The new due date must be in the future.',
        ]);

        if (! $extensionRequest->isPending()) {
            return back()->with('error', 'This extension request has already been reviewed.');
        }

        $assignments->approve(
            $extensionRequest,
            $request->user(),
            Carbon::parse($data['approved_due_at']),
            $data['reviewer_note'] ?? null
        );

        return back()->with('success', 'Extension approved. The participant has been notified.');
    }

    /**
     * Reject a participant's assignment extension request (optional note).
     */
    public function rejectExtension(
        Request $request,
        Course $course,
        AssignmentExtensionRequest $extensionRequest,
        ParticipantAssignmentService $assignments
    ) {
        $this->authoriseExtension($course, $extensionRequest);

        $data = $request->validate([
            'reviewer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $extensionRequest->isPending()) {
            return back()->with('error', 'This extension request has already been reviewed.');
        }

        $assignments->reject($extensionRequest, $request->user(), $data['reviewer_note'] ?? null);

        return back()->with('success', 'Extension request rejected. The participant has been notified.');
    }

    public function downloadSubmissionFile(Course $course, AssessmentAttempt $attempt)
    {
        $this->authorise($course);

        $attempt->loadMissing('assessment');

        abort_unless((int) $attempt->assessment?->course_id === (int) $course->id, 404);

        // Legacy single-file route: serves the submission's first file.
        $file = $this->files()->attemptFiles($attempt)->first();
        abort_unless($file, 404);

        return $this->files()->respond(request(), $file, true);
    }

    public function updateParticipant(Request $request, Course $course, Enrolment $enrolment)
    {
        $this->authorise($course);
        abort_unless((int) $enrolment->course_id === (int) $course->id, 404);

        $enrolment->update($request->validate([
            'status' => ['required', 'in:enrolled,in_progress,completed,withdrawn,failed'],
            'progress_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]));

        return back()->with('success', 'Participant progress updated.');
    }

    public function participantProgress(Course $course, Enrolment $enrolment)
    {
        $this->authorise($course);
        abort_unless((int) $enrolment->course_id === (int) $course->id, 404);

        $enrolment->load(['user', 'cohort']);

        $lessons = DB::table('lessons as l')
            ->join('course_modules as cm', 'cm.id', '=', 'l.course_module_id')
            ->leftJoin('lesson_progress as lp', function ($join) use ($enrolment) {
                $join->on('lp.lesson_id', '=', 'l.id')
                    ->where('lp.user_id', '=', $enrolment->user_id);
            })
            ->where('cm.course_id', $course->id)
            ->select(
                'l.id',
                'l.title',
                'l.is_published',
                'cm.id as module_id',
                'cm.title as module_title',
                'lp.first_opened_at',
                'lp.last_opened_at',
                'lp.completed_at',
                'lp.time_spent_seconds'
            )
            ->orderBy('cm.position')
            ->orderBy('l.position')
            ->get();

        $attempts = AssessmentAttempt::query()
            ->with('assessment')
            ->where('user_id', $enrolment->user_id)
            ->whereHas('assessment', fn ($query) => $query->where('course_id', $course->id))
            ->latest('submitted_at')
            ->get();

        $attendance = DB::table('attendance_sessions as session')
            ->leftJoin('attendance_records as record', function ($join) use ($enrolment) {
                $join->on('record.attendance_session_id', '=', 'session.id')
                    ->where('record.user_id', '=', $enrolment->user_id);
            })
            ->where('session.course_id', $course->id)
            ->select(
                'session.title',
                'session.session_date',
                'session.venue',
                'record.status',
                'record.remarks'
            )
            ->orderByDesc('session.session_date')
            ->get();

        $certificate = DB::table('certificates')
            ->where('course_id', $course->id)
            ->where('user_id', $enrolment->user_id)
            ->first();

        return view('instructor.participant-progress', [
            'course' => $course,
            'enrolment' => $enrolment,
            'lessons' => $lessons,
            'attempts' => $attempts,
            'attendance' => $attendance,
            'certificate' => $certificate,
        ]);
    }

    public function exportProgress(Course $course): StreamedResponse
    {
        $this->authorise($course);

        $enrolments = $course->enrolments()
            ->with('user')
            ->orderBy('id')
            ->get();

        $rows = $this->progressRows($course, $enrolments);

        $filename = 'course-progress-'.str($course->title)->slug().'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Participant',
                'Email',
                'Enrolment Status',
                'Lessons Completed',
                'Modules Completed',
                'Assignment Progress',
                'Quiz Progress',
                'Exam Progress',
                'Average Score',
                'Attendance',
                'Overall Progress %',
                'Last Activity',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['participant'],
                    $row['email'],
                    $row['status'],
                    $row['lessons_completed'].'/'.$row['lessons_total'],
                    $row['modules_completed'].'/'.$row['modules_total'],
                    $row['assignment_progress'],
                    $row['quiz_progress'],
                    $row['exam_progress'],
                    $row['average_score'],
                    $row['attendance'],
                    $row['overall_progress'],
                    $row['last_activity'],
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function previews(): FilePreviewService
    {
        return app(FilePreviewService::class);
    }

    private function files(): LearningFileService
    {
        return app(LearningFileService::class);
    }

    /** Copy legacy single-file columns of this course into the files tables. */
    private function syncLegacyFiles(Course $course): void
    {
        $this->files()->syncLessons(
            Lesson::query()
                ->whereHas('module', fn ($query) => $query->where('course_id', $course->id))
                ->whereNotNull('file_path')
                ->where('file_path', '!=', '')
                ->with('module')
                ->get()
        );

        $this->files()->syncAssessments(
            $course->assessments()->whereNotNull('attachment_path')->where('attachment_path', '!=', '')->get()
        );

        $this->files()->syncAttempts(
            AssessmentAttempt::query()
                ->whereHas('assessment', fn ($query) => $query->where('course_id', $course->id))
                ->whereNotNull('submission_file_path')
                ->where('submission_file_path', '!=', '')
                ->get()
        );
    }

    /** Delete the files ticked in "remove_files[]" that belong to the given relation. */
    private function removeSelectedFiles(Request $request, $relation): void
    {
        $ids = collect((array) $request->input('remove_files', []))->map(fn ($id) => (int) $id)->filter();

        if ($ids->isEmpty()) {
            return;
        }

        $relation->whereIn('id', $ids)->get()
            ->each(fn (LearningFile $file) => $this->files()->deleteLearningFile($file));
    }

    private function validateLesson(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'content' => ['nullable', 'string'],
            'content_type' => ['required', 'in:text,video,file,link,mixed'],
            'video_url' => ['nullable', 'url'],
            'external_url' => ['nullable', 'url'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer', 'min:1'],
            'is_published' => ['nullable', 'boolean'],
            'remove_file' => ['nullable', 'boolean'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['integer'],
        ] + $this->files()->uploadRules('resource_files', 'lesson_mimes', 'resource_file'), [
            'resource_files.max' => 'You can upload up to :max files at a time.',
        ]);
    }

    private function validateAssessment(Request $request): array
    {
        return $request->validate([
            'course_module_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:190'],
            'type' => ['required', 'in:quiz,assignment,exam'],
            'instructions' => ['nullable', 'string'],
            'pass_mark' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'opens_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'total_marks' => ['nullable', 'numeric', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
            'remove_attachment' => ['nullable', 'boolean'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['integer'],
        ] + $this->files()->uploadRules('assessment_files', 'assignment_mimes', 'assessment_file'), [
            'assessment_files.max' => 'You can upload up to :max files at a time.',
        ]);
    }

    private function progressRows(Course $course, Collection $enrolments): Collection
    {
        if ($enrolments->isEmpty()) {
            return collect();
        }

        $userIds = $enrolments->pluck('user_id')->unique()->values();

        $courseLessons = DB::table('lessons as l')
            ->join('course_modules as cm', 'cm.id', '=', 'l.course_module_id')
            ->where('cm.course_id', $course->id)
            ->where('l.is_published', true)
            ->select('l.id', 'l.course_module_id')
            ->get();

        $lessonIds = $courseLessons->pluck('id');
        $moduleLessonIds = $courseLessons->groupBy('course_module_id')
            ->map(fn ($rows) => $rows->pluck('id')->all());

        $completedProgress = $lessonIds->isEmpty()
            ? collect()
            : DB::table('lesson_progress')
                ->whereIn('lesson_id', $lessonIds)
                ->whereIn('user_id', $userIds)
                ->whereNotNull('completed_at')
                ->get(['user_id', 'lesson_id', 'completed_at', 'updated_at']);

        $completedByUser = $completedProgress
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('lesson_id')->map(fn ($id) => (int) $id)->unique());

        $assessments = $course->assessments()
            ->get(['id', 'type']);

        $assessmentIds = $assessments->pluck('id');

        $attempts = $assessmentIds->isEmpty()
            ? collect()
            : AssessmentAttempt::query()
                ->whereIn('assessment_id', $assessmentIds)
                ->whereIn('user_id', $userIds)
                ->whereIn('status', ['submitted', 'graded'])
                ->get(['assessment_id', 'user_id', 'percentage', 'updated_at']);

        $attemptsByUser = $attempts->groupBy('user_id');

        $attendanceRows = DB::table('attendance_sessions as session')
            ->leftJoin('attendance_records as record', 'record.attendance_session_id', '=', 'session.id')
            ->where('session.course_id', $course->id)
            ->whereIn('record.user_id', $userIds)
            ->get(['record.user_id', 'record.status', 'record.updated_at']);

        $attendanceByUser = $attendanceRows->groupBy('user_id');

        $assessmentTotals = [
            'assignment' => $assessments->where('type', 'assignment')->count(),
            'quiz' => $assessments->where('type', 'quiz')->count(),
            'exam' => $assessments->where('type', 'exam')->count(),
        ];

        return $enrolments->map(function ($enrolment) use (
            $courseLessons,
            $moduleLessonIds,
            $completedByUser,
            $attemptsByUser,
            $assessments,
            $assessmentTotals,
            $attendanceByUser
        ) {
            $userId = (int) $enrolment->user_id;
            $completedIds = $completedByUser->get($userId, collect());

            $modulesCompleted = $moduleLessonIds->filter(function ($lessonIds) use ($completedIds) {
                if ($lessonIds === []) {
                    return false;
                }

                return collect($lessonIds)->every(
                    fn ($lessonId) => $completedIds->contains((int) $lessonId)
                );
            })->count();

            $userAttempts = $attemptsByUser->get($userId, collect());

            $progressFor = function (string $type) use (
                $userAttempts,
                $assessments,
                $assessmentTotals
            ): string {
                $ids = $assessments->where('type', $type)->pluck('id');
                $attempted = $userAttempts
                    ->whereIn('assessment_id', $ids)
                    ->pluck('assessment_id')
                    ->unique()
                    ->count();

                return $attempted.'/'.$assessmentTotals[$type];
            };

            $scoreValues = $userAttempts->pluck('percentage')->filter(
                fn ($value) => $value !== null
            );

            $attendanceRows = $attendanceByUser->get($userId, collect());
            $attendanceTotal = $attendanceRows->count();
            $attendancePresent = $attendanceRows
                ->whereIn('status', ['present', 'late'])
                ->count();

            $lastActivity = collect([
                $completedIds->isNotEmpty()
                    ? DB::table('lesson_progress')
                        ->where('user_id', $userId)
                        ->whereIn('lesson_id', $completedIds)
                        ->max('updated_at')
                    : null,
                $userAttempts->max('updated_at'),
                $attendanceRows->max('updated_at'),
            ])->filter()->max();

            return [
                'enrolment_id' => $enrolment->id,
                'participant' => $enrolment->user?->name ?? 'Participant',
                'email' => $enrolment->user?->email ?? '',
                'status' => $enrolment->status,
                'lessons_completed' => $completedIds->count(),
                'lessons_total' => $courseLessons->count(),
                'modules_completed' => $modulesCompleted,
                'modules_total' => $moduleLessonIds->count(),
                'assignment_progress' => $progressFor('assignment'),
                'quiz_progress' => $progressFor('quiz'),
                'exam_progress' => $progressFor('exam'),
                'average_score' => $scoreValues->isNotEmpty()
                    ? round((float) $scoreValues->avg(), 1)
                    : null,
                'attendance' => $attendanceTotal > 0
                    ? $attendancePresent.'/'.$attendanceTotal
                    : '—',
                'overall_progress' => round((float) ($enrolment->progress_percent ?? 0), 1),
                'last_activity' => $lastActivity
                    ? \Illuminate\Support\Carbon::parse($lastActivity)->format('d M Y H:i')
                    : '—',
            ];
        });
    }

    private function authorise(Course $course): void
    {
        $user = auth()->user();

        abort_unless($user && $user->isStaff() && $user->isActive(), 403);

        $isAdmin = $user->isSuperAdmin()
            || (
                method_exists($user, 'hasAnyRole')
                && $user->hasAnyRole([
                    'administrator',
                    'super-administrator',
                    'super-admin',
                ])
            );

        if ($isAdmin) {
            return;
        }

        abort_unless(
            $user->hasAnyRole(['instructor', 'trainer'])
            && $user->instructedCourses()->whereKey($course->id)->exists(),
            403
        );
    }

    private function authoriseExtension(Course $course, AssignmentExtensionRequest $extensionRequest): void
    {
        $this->authorise($course);
        $extensionRequest->loadMissing('assessment');
        abort_unless((int) $extensionRequest->assessment?->course_id === (int) $course->id, 404);
    }

    private function authoriseModule(Course $course, CourseModule $module): void
    {
        $this->authorise($course);
        abort_unless((int) $module->course_id === (int) $course->id, 404);
    }

    private function authoriseLesson(
        Course $course,
        CourseModule $module,
        Lesson $lesson
    ): void {
        $this->authoriseModule($course, $module);

        abort_unless(
            (int) $lesson->course_module_id === (int) $module->id,
            404
        );
    }

    private function authoriseAssessment(Course $course, Assessment $assessment): void
    {
        $this->authorise($course);
        abort_unless((int) $assessment->course_id === (int) $course->id, 404);
    }
}
