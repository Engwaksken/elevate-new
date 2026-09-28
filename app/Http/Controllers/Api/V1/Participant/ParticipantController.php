<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CourseAnnouncement;
use App\Models\Enrolment;
use App\Models\ParticipantDeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ParticipantController extends Controller
{
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

        $course->modules->each(function ($module) {
            $module->lessons->each(function ($lesson) {
                $lesson->resource_url = $lesson->file_path
                    ? Storage::disk('public')->url($lesson->file_path)
                    : null;
            });
        });

        $course->assessments->each(function ($assessment) {
            $assessment->attachment_url = $assessment->attachment_path
                ? Storage::disk('public')->url($assessment->attachment_path)
                : null;
        });

        return response()->json(['course' => $course]);
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
            $query->orderByRaw('due_at IS NULL, due_at ASC')->paginate(20)
        );
    }

    public function submitAssignment(Request $request, Assessment $assessment)
    {
        $user = $request->user();
        $this->ensureParticipantOwnsCourse($request, $assessment->course);
        abort_unless($assessment->is_published, 404);

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
            404
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
            ->exists(),404);

        return response()->json(['message'=>'Notification marked as read.']);
    }

    public function lessonProgress(Request $request, int $lesson)
    {
        $user = $request->user();

        $lessonRow = DB::table('lessons as l')
            ->join('course_modules as cm','cm.id','=','l.course_module_id')
            ->join('enrolments as e','e.course_id','=','cm.course_id')
            ->where('l.id',$lesson)
            ->where('e.user_id',$user->id)
            ->select('l.id','cm.course_id')
            ->first();

        abort_unless($lessonRow,403);

        $data = $request->validate([
            'completed' => ['required','boolean'],
            'time_spent_seconds' => ['nullable','integer','min:0'],
        ]);

        $existing = DB::table('lesson_progress')
            ->where('lesson_id',$lesson)
            ->where('user_id',$user->id)
            ->first();

        DB::table('lesson_progress')->updateOrInsert(
            ['lesson_id'=>$lesson,'user_id'=>$user->id],
            [
                'first_opened_at'=>$existing?->first_opened_at ?? now(),
                'last_opened_at'=>now(),
                'completed_at'=>$data['completed'] ? now() : null,
                'time_spent_seconds'=>($existing?->time_spent_seconds ?? 0) + (int)($data['time_spent_seconds'] ?? 0),
                'created_at'=>$existing?->created_at ?? now(),
                'updated_at'=>now(),
            ]
        );

        $total = DB::table('lessons')
            ->join('course_modules','course_modules.id','=','lessons.course_module_id')
            ->where('course_modules.course_id',$lessonRow->course_id)
            ->where('lessons.is_published',true)
            ->count();

        $completed = DB::table('lesson_progress')
            ->join('lessons','lessons.id','=','lesson_progress.lesson_id')
            ->join('course_modules','course_modules.id','=','lessons.course_module_id')
            ->where('course_modules.course_id',$lessonRow->course_id)
            ->where('lesson_progress.user_id',$user->id)
            ->whereNotNull('lesson_progress.completed_at')
            ->count();

        $progress = $total > 0 ? round(($completed/$total)*100,2) : 0;

        DB::table('enrolments')
            ->where('course_id',$lessonRow->course_id)
            ->where('user_id',$user->id)
            ->update([
                'progress_percent'=>$progress,
                'status'=>$progress >= 100 ? 'completed' : 'in_progress',
                'started_at'=>DB::raw('COALESCE(started_at, CURRENT_TIMESTAMP)'),
                'completed_at'=>$progress >= 100 ? now() : null,
                'updated_at'=>now(),
            ]);

        return response()->json(['message'=>'Progress saved.','course_progress_percent'=>$progress]);
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
                    'lesson_progress' => $this->lessonProgress(
                        Request::create('/', 'POST', [
                            'completed'=>(bool)($payload['completed'] ?? false),
                            'time_spent_seconds'=>(int)($payload['time_spent_seconds'] ?? 0),
                        ])->setUserResolver(fn () => $request->user()),
                        (int)($payload['lesson_id'] ?? 0)
                    )->getData(true),
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
            } catch (\Throwable $e) {
                report($e);
                $results[] = [
                    'client_operation_id'=>$operation['client_operation_id'],
                    'status'=>'failed',
                    'message'=>'The operation could not be processed.',
                ];
            }
        }

        return response()->json(['results'=>$results,'server_time'=>now()->toIso8601String()]);
    }

    public function sync(Request $request)
    {
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
            'assignments'=>$assignments,
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

    private function ensureParticipantOwnsCourse(Request $request, Course $course): void
    {
        abort_unless(
            Enrolment::where('course_id',$course->id)
                ->where('user_id',$request->user()->id)
                ->exists(),
            403
        );
    }
}
