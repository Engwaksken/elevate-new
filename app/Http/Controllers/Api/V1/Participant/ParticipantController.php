<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CourseAnnouncement;
use App\Models\Enrolment;
use App\Models\LearningFile;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\ParticipantDeviceToken;
use App\Services\Learning\ModuleAccessService;
use App\Services\Participant\ParticipantLessonService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ParticipantController extends Controller
{
    public function __construct(
        private readonly ParticipantLessonService $lessonService,
        private readonly ModuleAccessService $moduleAccess
    ) {
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('profile'),
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();

        $enrolments = Enrolment::query()
            ->with('course')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $courseIds = $enrolments->pluck('course_id');

        $unread = DB::table('user_notifications')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $nextSession = $this->mentorshipBase($user->id)
            ->where('ms.scheduled_at', '>=', now())
            ->where('ms.status', 'scheduled')
            ->orderBy('ms.scheduled_at')
            ->first();

        return response()->json([
            'summary' => [
                'courses' => $enrolments->count(),
                'in_progress' => $enrolments->where('status', 'in_progress')->count(),
                'completed' => $enrolments->where('status', 'completed')->count(),
                'pending_assignments' => Assessment::query()
                    ->whereIn('course_id', $courseIds)
                    ->where('is_published', true)
                    ->whereIn('type', ['assignment','quiz','exam'])
                    ->where(function ($q) {
                        $q->whereNull('due_at')->orWhere('due_at', '>=', now());
                    })
                    ->count(),
                'unread_notifications' => $unread,
            ],
            'courses' => $enrolments->map(fn ($enrolment) => [
                'id' => $enrolment->course?->id,
                'title' => $enrolment->course?->title,
                'status' => $enrolment->status,
                'progress_percent' => (float) $enrolment->progress_percent,
            ])->values(),
            'next_mentorship_session' => $nextSession,
            'last_synced_at' => now()->toIso8601String(),
        ]);
    }

    public function courses(Request $request)
    {
        $user = $request->user();

        $query = Course::query()
            ->whereHas('enrolments', fn ($q) => $q->where('user_id', $user->id))
            ->with(['cohorts'])
            ->withCount('modules');

        if ($request->filled('search')) {
            $term = trim((string) $request->get('search'));
            $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->get('updated_since'));
        }

        return response()->json($query->orderBy('title')->paginate(20));
    }

    public function course(Request $request, Course $course)
    {
        $this->ensureParticipantOwnsCourse($request, $course);

        $course->load([
            'cohorts',
            'modules' => fn ($q) => $q->where('is_published', true)->orderBy('position'),
            'modules.lessons' => fn ($q) => $q->where('is_published', true)->orderBy('position'),
            'assessments' => fn ($q) => $q->where('is_published', true)->orderBy('due_at'),
            'announcements' => fn ($q) => $q
                ->where(fn ($x) => $x->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->where(fn ($x) => $x->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest('published_at'),
        ]);

        $user = $request->user();
        $lessonIds = $course->modules->flatMap(fn ($module) => $module->lessons->pluck('id'));

        $learningFiles = LearningFile::whereIn('lesson_id', $lessonIds)->orderBy('id')->get()->groupBy('lesson_id');
        $progressRows = LessonProgress::where('user_id', $user->id)->whereIn('lesson_id', $lessonIds)->get()->keyBy('lesson_id');

        $payload = $course->toArray();
        $enrolment = Enrolment::where('course_id', $course->id)->where('user_id', $user->id)->first();

        $payload['enrolment'] = $enrolment ? [
            'status' => $enrolment->status,
            'progress_percent' => (float) $enrolment->progress_percent,
        ] : null;

        // Lessons are serialised through the shared presenter so the app gets an
        // authenticated resource_url (API download route) instead of a public /storage URL.
        $payload['modules'] = $course->modules->map(function ($module) use ($user, $learningFiles, $progressRows) {
            $locked = ! $this->moduleAccess->canAccess($module, $user);
            $moduleData = $module->withoutRelations()->toArray();
            $moduleData['is_locked'] = $locked;
            $moduleData['lessons'] = $module->lessons->map(fn ($lesson) => $this->lessonService->present(
                $lesson->setRelation('module', $module),
                $user,
                $learningFiles->get($lesson->id, collect()),
                $progressRows->get($lesson->id) ?? new LessonProgress(),
                $locked,
                true
            ))->values()->all();

            return $moduleData;
        })->values()->all();

        $payload['assessments'] = $course->assessments
            ->map(fn ($assessment) => $this->lessonService->presentAssessment($assessment))
            ->values()
            ->all();

        return response()->json(['course' => $payload]);
    }

    public function assignments(Request $request)
    {
        $courseIds = Enrolment::where('user_id', $request->user()->id)->pluck('course_id');

        $query = Assessment::query()
            ->with('course:id,title')
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true);

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->get('updated_since'));
        }

        return response()->json(
            $query->orderByRaw('due_at IS NULL, due_at ASC')
                ->paginate(20)
                ->through(fn ($assessment) => $this->lessonService->presentAssessment($assessment))
        );
    }

    public function submitAssignment(Request $request, Assessment $assessment)
    {
        $user = $request->user();
        $this->ensureParticipantOwnsCourse($request, $assessment->course);
        abort_unless($assessment->is_published, 404, 'Assessment not found.');

        $data = $request->validate([
            'submission_text' => ['nullable','string'],
            'submission_file' => ['nullable','file','max:51200','mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,jpg,jpeg,png,webp'],
            'client_submission_id' => ['nullable','string','max:190'],
        ]);

        if (! empty($data['client_submission_id'])) {
            $duplicate = AssessmentAttempt::where('client_submission_id', $data['client_submission_id'])
                ->where('user_id', $user->id)
                ->first();

            if ($duplicate) {
                return response()->json([
                    'message' => 'Submission already received.',
                    'attempt' => $duplicate,
                    'duplicate' => true,
                ]);
            }
        }

        $existingAttempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->count();

        abort_if($existingAttempts >= $assessment->max_attempts, 422, 'Maximum attempts reached.');

        $filePath = $request->hasFile('submission_file')
            ? $request->file('submission_file')->store(
                'participant-submissions/'.$user->id.'/'.$assessment->id,
                'local'
            )
            : null;

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'attempt_number' => $existingAttempts + 1,
            'client_submission_id' => $data['client_submission_id'] ?? null,
            'submission_text' => $data['submission_text'] ?? null,
            'submission_file_path' => $filePath,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        return response()->json(['message' => 'Submission received.', 'attempt' => $attempt], 201);
    }

    public function mentorship(Request $request)
    {
        $userId = $request->user()->id;

        $matches = DB::table('mentor_matches as mm')
            ->join('users as mentor', 'mentor.id', '=', 'mm.mentor_user_id')
            ->leftJoin('mentor_profiles as mp', 'mp.user_id', '=', 'mentor.id')
            ->where('mm.mentee_user_id', $userId)
            ->select(
                'mm.id','mm.status','mm.start_date','mm.end_date','mm.updated_at',
                'mentor.id as mentor_id','mentor.name as mentor_name','mentor.email as mentor_email',
                'mp.organisation','mp.job_title','mp.industry','mp.professional_bio'
            )
            ->orderByDesc('mm.id')
            ->get();

        $sessions = $this->mentorshipBase($userId)
            ->orderBy('ms.scheduled_at')
            ->get();

        $goals = DB::table('mentorship_goals as mg')
            ->join('mentor_matches as mm', 'mm.id', '=', 'mg.mentor_match_id')
            ->where('mm.mentee_user_id', $userId)
            ->select('mg.*')
            ->orderBy('mg.target_date')
            ->get();

        return response()->json([
            'matches' => $matches,
            'sessions' => $sessions,
            'goals' => $goals,
        ]);
    }

    public function jobs(Request $request)
    {
        $userId = $request->user()->id;

        $query = DB::table('jobs as j')
            ->join('employers as e', 'e.id', '=', 'j.employer_id')
            ->leftJoin('saved_jobs as sj', function ($join) use ($userId) {
                $join->on('sj.job_id', '=', 'j.id')
                    ->where('sj.user_id', '=', $userId);
            })
            ->where('j.status', 'published')
            ->whereNull('j.deleted_at')
            ->where(function ($q) {
                $q->whereNull('j.application_deadline')
                    ->orWhere('j.application_deadline', '>=', today());
            })
            ->select(
                'j.id','j.title','j.category','j.industry','j.location','j.country',
                'j.employment_type','j.work_arrangement','j.experience_level',
                'j.education_level','j.description','j.requirements','j.skills',
                'j.application_deadline','j.published_at','j.updated_at',
                'e.company_name',
                DB::raw('CASE WHEN sj.job_id IS NULL THEN 0 ELSE 1 END as is_saved')
            );

        if ($request->filled('search')) {
            $term = trim((string) $request->get('search'));
            $query->where(function ($q) use ($term) {
                $q->where('j.title','like',"%{$term}%")
                    ->orWhere('j.category','like',"%{$term}%")
                    ->orWhere('j.location','like',"%{$term}%")
                    ->orWhere('e.company_name','like',"%{$term}%");
            });
        }

        if ($request->filled('updated_since')) {
            $query->where('j.updated_at', '>', $request->get('updated_since'));
        }

        return response()->json($query->orderByDesc('j.published_at')->paginate(20));
    }

    public function saveJob(Request $request, int $job)
    {
        abort_unless(
            DB::table('jobs')->where('id',$job)->where('status','published')->whereNull('deleted_at')->exists(),
            404,
            'Job not found or no longer open.'
        );

        DB::table('saved_jobs')->updateOrInsert(
            ['user_id'=>$request->user()->id,'job_id'=>$job],
            ['created_at'=>now(),'updated_at'=>now()]
        );

        return response()->json(['message'=>'Job saved.']);
    }

    public function unsaveJob(Request $request, int $job)
    {
        DB::table('saved_jobs')
            ->where('user_id',$request->user()->id)
            ->where('job_id',$job)
            ->delete();

        return response()->json(['message'=>'Job removed from saved jobs.']);
    }

    public function events(Request $request)
    {
        $user = $request->user();
        $courseIds = Enrolment::where('user_id',$user->id)->pluck('course_id');
        $cohortIds = Enrolment::where('user_id',$user->id)->whereNotNull('cohort_id')->pluck('cohort_id');

        $query = DB::table('events')
            ->where('is_published', true)
            ->whereNull('deleted_at')
            ->where('starts_at', '>=', now()->subDay())
            ->where(function ($q) use ($courseIds,$cohortIds) {
                $q->whereNull('course_id')
                    ->whereNull('cohort_id')
                    ->orWhereIn('course_id',$courseIds)
                    ->orWhereIn('cohort_id',$cohortIds);
            });

        if ($request->filled('updated_since')) {
            $query->where('updated_at','>',$request->get('updated_since'));
        }

        return response()->json($query->orderBy('starts_at')->paginate(20));
    }

    public function notifications(Request $request)
    {
        $query = DB::table('user_notifications')
            ->where('user_id',$request->user()->id);

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at','>',$request->get('updated_since'));
        }

        return response()->json($query->latest()->paginate(30));
    }

    public function markNotificationRead(Request $request, int $notification)
    {
        $updated = DB::table('user_notifications')
            ->where('id',$notification)
            ->where('user_id',$request->user()->id)
            ->update(['read_at'=>now(),'updated_at'=>now()]);

        abort_unless($updated || DB::table('user_notifications')
            ->where('id',$notification)
            ->where('user_id',$request->user()->id)
            ->exists(),404,'Notification not found.');

        return response()->json(['message'=>'Notification marked as read.']);
    }

    public function announcements(Request $request)
    {
        $courseIds = Enrolment::where('user_id', $request->user()->id)->pluck('course_id');

        $query = CourseAnnouncement::query()
            ->with('course:id,title')
            ->whereIn('course_id', $courseIds)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->get('updated_since'));
        }

        return response()->json($query->latest('published_at')->paginate(20));
    }

    public function processOfflineActions(Request $request)
    {
        // The Flutter app posts {"actions":[...]}; the PWA posts {"operations":[...]}.
        // Accept both, and "client_action_id" as an alias of "client_operation_id".
        if (! $request->has('operations') && is_array($request->input('actions'))) {
            $request->merge([
                'operations' => collect($request->input('actions'))
                    ->map(fn ($item) => is_array($item) ? $item + [
                        'client_operation_id' => $item['client_action_id'] ?? null,
                    ] : $item)
                    ->all(),
            ]);
        }

        $data = $request->validate([
            'operations' => ['required','array','max:100'],
            'operations.*.client_operation_id' => ['required','string','max:190'],
            'operations.*.type' => ['required','string','in:lesson_progress,save_job,unsave_job,notification_read'],
            'operations.*.payload' => ['nullable','array'],
        ]);

        $results = [];

        foreach ($data['operations'] as $operation) {
            $existing = DB::table('mobile_sync_operations')
                ->where('user_id',$request->user()->id)
                ->where('client_operation_id',$operation['client_operation_id'])
                ->first();

            if ($existing) {
                $results[] = [
                    'client_operation_id'=>$operation['client_operation_id'],
                    'status'=>'duplicate',
                    'result'=>$existing->result ? json_decode($existing->result,true) : null,
                ];
                continue;
            }

            try {
                $payload = $operation['payload'] ?? [];
                $result = match ($operation['type']) {
                    'lesson_progress' => $this->offlineLessonProgress($request, $payload),
                    'save_job' => $this->saveJob($request,(int)($payload['job_id'] ?? 0))->getData(true),
                    'unsave_job' => $this->unsaveJob($request,(int)($payload['job_id'] ?? 0))->getData(true),
                    'notification_read' => $this->markNotificationRead($request,(int)($payload['notification_id'] ?? 0))->getData(true),
                };

                DB::table('mobile_sync_operations')->insert([
                    'user_id'=>$request->user()->id,
                    'client_operation_id'=>$operation['client_operation_id'],
                    'operation_type'=>$operation['type'],
                    'payload'=>json_encode($payload),
                    'status'=>'processed',
                    'result'=>json_encode($result),
                    'processed_at'=>now(),
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);

                $results[] = [
                    'client_operation_id'=>$operation['client_operation_id'],
                    'status'=>'processed',
                    'result'=>$result,
                ];
            } catch (HttpExceptionInterface $e) {
                $results[] = [
                    'client_operation_id'=>$operation['client_operation_id'],
                    'status'=>'failed',
                    'code'=>$e->getStatusCode(),
                    'message'=>$e->getMessage() ?: 'The operation could not be processed.',
                ];
            } catch (ModelNotFoundException $e) {
                $results[] = [
                    'client_operation_id'=>$operation['client_operation_id'],
                    'status'=>'failed',
                    'code'=>404,
                    'message'=>'The item referenced by this operation no longer exists.',
                ];
            } catch (\Throwable $e) {
                report($e);
                $results[] = [
                    'client_operation_id'=>$operation['client_operation_id'],
                    'status'=>'failed',
                    'code'=>500,
                    'message'=>'The operation could not be processed.',
                ];
            }
        }

        return response()->json(['results'=>$results,'server_time'=>now()->toIso8601String()]);
    }

    public function sync(Request $request)
    {
        // "since" is accepted as an alias (used by the Flutter app).
        if (! $request->filled('last_synced_at') && $request->filled('since')) {
            $request->merge(['last_synced_at' => $request->get('since')]);
        }

        $request->validate(['last_synced_at'=>['nullable','date']]);

        $user = $request->user();
        $since = $request->get('last_synced_at');

        $enrolments = Enrolment::where('user_id',$user->id)->get();
        $courseIds = $enrolments->pluck('course_id');
        $cohortIds = $enrolments->pluck('cohort_id')->filter();

        $courses = Course::withTrashed()
            ->whereIn('id',$courseIds)
            ->when($since, fn ($q) => $q->where('updated_at','>',$since))
            ->get();

        $assignments = Assessment::whereIn('course_id',$courseIds)
            ->where('is_published',true)
            ->when($since, fn ($q) => $q->where('updated_at','>',$since))
            ->get();

        $announcements = CourseAnnouncement::withTrashed()
            ->whereIn('course_id',$courseIds)
            ->when($since, fn ($q) => $q->where('updated_at','>',$since))
            ->get();

        $jobs = DB::table('jobs')
            ->when($since, fn ($q) => $q->where('updated_at','>',$since))
            ->where(function ($q) {
                $q->where('status','published')->orWhereNotNull('deleted_at');
            })
            ->get();

        $events = DB::table('events')
            ->when($since, fn ($q) => $q->where('updated_at','>',$since))
            ->where(function ($q) use ($courseIds,$cohortIds) {
                $q->whereNull('course_id')->whereNull('cohort_id')
                    ->orWhereIn('course_id',$courseIds)
                    ->orWhereIn('cohort_id',$cohortIds);
            })
            ->get();

        $notifications = DB::table('user_notifications')
            ->where('user_id',$user->id)
            ->when($since, fn ($q) => $q->where('updated_at','>',$since))
            ->get();

        $mentorship = $this->mentorshipBase($user->id)
            ->when($since, fn ($q) => $q->where('ms.updated_at','>',$since))
            ->get();

        $lessonProgress = DB::table('lesson_progress')
            ->where('user_id',$user->id)
            ->when($since, fn ($q) => $q->where('updated_at','>',$since))
            ->get();

        $reminders = collect();

        foreach ($assignments->whereNotNull('due_at') as $item) {
            $reminders->push([
                'type'=>'assignment_deadline',
                'source_id'=>$item->id,
                'title'=>$item->title,
                'scheduled_at'=>$item->due_at,
            ]);
        }

        foreach ($mentorship->where('status','scheduled') as $item) {
            $reminders->push([
                'type'=>'mentorship_session',
                'source_id'=>$item->id,
                'title'=>$item->title,
                'scheduled_at'=>$item->scheduled_at,
            ]);
        }

        foreach ($events->where('is_published',1) as $item) {
            if ($item->starts_at) {
                $reminders->push([
                    'type'=>'event',
                    'source_id'=>$item->id,
                    'title'=>$item->title,
                    'scheduled_at'=>$item->starts_at,
                ]);
            }
        }

        return response()->json([
            'server_time'=>now()->toIso8601String(),
            'last_synced_at'=>now()->toIso8601String(),
            'enrolments'=>$enrolments,
            'courses'=>$courses,
            'assignments'=>$assignments->map(fn ($assessment) => $this->lessonService->presentAssessment($assessment))->values(),
            'announcements'=>$announcements,
            'mentorship'=>$mentorship,
            'jobs'=>$jobs,
            'events'=>$events,
            'notifications'=>$notifications,
            'lesson_progress'=>$lessonProgress,
            'local_reminders'=>$reminders->values(),
        ]);
    }

    public function deviceToken(Request $request)
    {
        // The Flutter app sends "fcm_token"; accept it as an alias of "token".
        if (! $request->filled('token') && $request->filled('fcm_token')) {
            $request->merge(['token' => $request->input('fcm_token')]);
        }

        $data = $request->validate([
            'device_id'=>['required','string','max:190'],
            'token'=>['required','string','max:4000'],
            'platform'=>['required','in:android,ios,web'],
            'app_version'=>['nullable','string','max:50'],
        ]);

        $device = ParticipantDeviceToken::updateOrCreate(
            ['user_id'=>$request->user()->id,'device_id'=>$data['device_id']],
            [
                'token'=>$data['token'],
                'platform'=>$data['platform'],
                'app_version'=>$data['app_version'] ?? null,
                'last_seen_at'=>now(),
            ]
        );

        return response()->json(['message'=>'Device registered.','device'=>$device]);
    }

    public function removeDeviceToken(Request $request)
    {
        $data = $request->validate([
            'device_id'=>['required','string','max:190'],
        ]);

        $deleted = ParticipantDeviceToken::where('user_id',$request->user()->id)
            ->where('device_id',$data['device_id'])
            ->delete();

        return response()->json([
            'message'=>$deleted ? 'Device unregistered.' : 'Device was not registered.',
            'removed'=>(bool) $deleted,
        ]);
    }

    /**
     * Apply a queued lesson_progress operation (payload: lesson_id, completed, time_spent_seconds).
     */
    private function offlineLessonProgress(Request $request, array $payload): array
    {
        $lesson = Lesson::findOrFail((int) ($payload['lesson_id'] ?? 0));
        $user = $request->user();

        $this->lessonService->authorize($user, $lesson);

        return $this->lessonService->saveProgress(
            $user,
            $lesson,
            filter_var($payload['completed'] ?? false, FILTER_VALIDATE_BOOLEAN),
            (int) ($payload['time_spent_seconds'] ?? 0)
        );
    }

    private function mentorshipBase(int $userId)
    {
        return DB::table('mentorship_sessions as ms')
            ->join('mentor_matches as mm','mm.id','=','ms.mentor_match_id')
            ->join('users as mentor','mentor.id','=','mm.mentor_user_id')
            ->where('mm.mentee_user_id',$userId)
            ->select(
                'ms.id','ms.mentor_match_id','ms.title','ms.agenda','ms.scheduled_at',
                'ms.duration_minutes','ms.meeting_link','ms.venue','ms.status',
                'ms.session_notes','ms.agreed_actions','ms.next_session_at','ms.updated_at',
                'mentor.id as mentor_id','mentor.name as mentor_name'
            );
    }

    private function ensureParticipantOwnsCourse(Request $request, ?Course $course): void
    {
        abort_unless($course, 404, 'Course not found.');

        abort_unless(
            Enrolment::where('course_id',$course->id)
                ->where('user_id',$request->user()->id)
                ->exists(),
            403,
            'You are not enrolled in this course.'
        );
    }
}
