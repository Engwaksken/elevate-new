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
use Illuminate\Support\Facades\Storage;

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
            ],
            'courses' => $enrolments->map(fn ($enrolment) => [
                'id' => $enrolment->course?->id,
                'title' => $enrolment->course?->title,
                'status' => $enrolment->status,
                'progress_percent' => (float) $enrolment->progress_percent,
            ])->values(),
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

        return response()->json(
            $query->orderBy('title')->paginate(20)
        );
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
                ->where(function ($x) {
                    $x->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->where(function ($x) {
                    $x->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
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
        $user = $request->user();

        $courseIds = Enrolment::where('user_id', $user->id)->pluck('course_id');

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

        $course = $assessment->course;
        $this->ensureParticipantOwnsCourse($request, $course);

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

        $filePath = null;

        if ($request->hasFile('submission_file')) {
            $filePath = $request->file('submission_file')->store(
                'participant-submissions/'.$user->id.'/'.$assessment->id,
                'local'
            );
        }

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

        return response()->json([
            'message' => 'Submission received.',
            'attempt' => $attempt,
        ], 201);
    }

    public function announcements(Request $request)
    {
        $user = $request->user();
        $courseIds = Enrolment::where('user_id', $user->id)->pluck('course_id');

        $query = CourseAnnouncement::query()
            ->with('course:id,title')
            ->whereIn('course_id', $courseIds)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->get('updated_since'));
        }

        return response()->json(
            $query->latest('published_at')->paginate(20)
        );
    }

    public function sync(Request $request)
    {
        $request->validate([
            'last_synced_at' => ['nullable','date'],
        ]);

        $user = $request->user();
        $since = $request->get('last_synced_at');

        $enrolments = Enrolment::query()
            ->where('user_id', $user->id)
            ->get(['id','course_id','cohort_id','status','progress_percent','final_score','updated_at']);

        $courseIds = $enrolments->pluck('course_id');

        $courses = Course::query()
            ->whereIn('id', $courseIds)
            ->when($since, fn ($q) => $q->where('updated_at', '>', $since))
            ->get();

        $assignments = Assessment::query()
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->when($since, fn ($q) => $q->where('updated_at', '>', $since))
            ->get();

        $announcements = CourseAnnouncement::query()
            ->whereIn('course_id', $courseIds)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->when($since, fn ($q) => $q->where('updated_at', '>', $since))
            ->get();

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'last_synced_at' => now()->toIso8601String(),
            'enrolments' => $enrolments,
            'courses' => $courses,
            'assignments' => $assignments,
            'announcements' => $announcements,
        ]);
    }

    public function deviceToken(Request $request)
    {
        $data = $request->validate([
            'device_id' => ['required','string','max:190'],
            'token' => ['required','string','max:4000'],
            'platform' => ['required','in:android,ios,web'],
            'app_version' => ['nullable','string','max:50'],
        ]);

        $device = ParticipantDeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'device_id' => $data['device_id'],
            ],
            [
                'token' => $data['token'],
                'platform' => $data['platform'],
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Device registered.',
            'device' => $device,
        ]);
    }

    private function ensureParticipantOwnsCourse(Request $request, Course $course): void
    {
        abort_unless(
            Enrolment::where('course_id', $course->id)
                ->where('user_id', $request->user()->id)
                ->exists(),
            403
        );
    }
}
