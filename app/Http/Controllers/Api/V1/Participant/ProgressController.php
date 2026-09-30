<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssignmentExtensionRequest;
use App\Models\Enrolment;
use App\Services\Participant\ParticipantAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GET /progress: the participant's learning, assignment, mentorship and event summary.
 * Every section is a fixed number of aggregate queries (no per-course / per-item queries).
 */
class ProgressController extends Controller
{
    public function __construct(private readonly ParticipantAssignmentService $assignments)
    {
    }

    public function show(Request $request)
    {
        $user = $request->user();

        $enrolments = Enrolment::query()
            ->with('course:id,title')
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get();

        $courseIds = $enrolments->pluck('course_id')->all();

        // Lessons: published lessons in published modules of enrolled courses.
        $lessonStats = DB::table('lessons as l')
            ->join('course_modules as cm', 'cm.id', '=', 'l.course_module_id')
            ->leftJoin('lesson_progress as lp', function ($join) use ($user) {
                $join->on('lp.lesson_id', '=', 'l.id')->where('lp.user_id', '=', $user->id);
            })
            ->whereIn('cm.course_id', $courseIds)
            ->where('l.is_published', true)
            ->where('cm.is_published', true)
            ->groupBy('cm.course_id')
            ->selectRaw('cm.course_id as course_id')
            ->selectRaw('COUNT(l.id) as lessons_total')
            ->selectRaw('SUM(CASE WHEN lp.completed_at IS NOT NULL THEN 1 ELSE 0 END) as lessons_completed')
            ->selectRaw('COALESCE(SUM(lp.time_spent_seconds), 0) as time_spent_seconds')
            ->selectRaw('MAX(lp.updated_at) as last_lesson_activity')
            ->get()
            ->keyBy('course_id');

        $lastSubmissionByCourse = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.user_id', $user->id)
            ->whereIn('a.course_id', $courseIds)
            ->whereNotNull('aa.submitted_at')
            ->groupBy('a.course_id')
            ->selectRaw('a.course_id as course_id, MAX(aa.submitted_at) as last_submitted')
            ->pluck('last_submitted', 'course_id');

        $courses = $enrolments->map(function (Enrolment $enrolment) use ($lessonStats, $lastSubmissionByCourse) {
            $stats = $lessonStats->get($enrolment->course_id);
            $total = (int) ($stats->lessons_total ?? 0);
            $done = (int) ($stats->lessons_completed ?? 0);

            $last = collect([$stats->last_lesson_activity ?? null, $lastSubmissionByCourse->get($enrolment->course_id)])
                ->filter()
                ->map(fn ($value) => Carbon::parse($value))
                ->max();

            return [
                'id' => $enrolment->course_id,
                'title' => $enrolment->course?->title,
                'status' => $enrolment->status,
                'progress_percent' => $total > 0 ? round($done / $total * 100, 2) : 0,
                'lessons_total' => $total,
                'lessons_completed' => $done,
                'time_spent_seconds' => (int) ($stats->time_spent_seconds ?? 0),
                'last_activity_at' => $last?->toIso8601String(),
            ];
        })->values();

        // Assignments: published assessments in enrolled courses.
        $assessments = Assessment::query()
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->get(['id', 'course_id', 'title', 'type', 'due_at', 'max_attempts', 'is_published']);

        $context = $this->assignments->context($user, $assessments->pluck('id'));

        $attemptFlags = DB::table('assessment_attempts')
            ->where('user_id', $user->id)
            ->whereIn('assessment_id', $assessments->pluck('id'))
            ->groupBy('assessment_id')
            ->selectRaw('assessment_id')
            ->selectRaw("SUM(CASE WHEN status IN ('submitted','graded') THEN 1 ELSE 0 END) as submitted")
            ->selectRaw("SUM(CASE WHEN status = 'graded' THEN 1 ELSE 0 END) as graded")
            ->get()
            ->keyBy('assessment_id');

        $submitted = $graded = $pending = $overdue = 0;

        foreach ($assessments as $assessment) {
            $flags = $attemptFlags->get($assessment->id);

            if ((int) ($flags->graded ?? 0) > 0) {
                $graded++;
            }

            if ((int) ($flags->submitted ?? 0) > 0) {
                $submitted++;
            } elseif ($this->assignments->state($assessment, $context)['is_overdue']) {
                $overdue++;
            } else {
                $pending++;
            }
        }

        // Mentorship: sessions where she is the mentee.
        $mentorship = DB::table('mentorship_sessions as ms')
            ->join('mentor_matches as mm', 'mm.id', '=', 'ms.mentor_match_id')
            ->where('mm.mentee_user_id', $user->id)
            ->selectRaw('COUNT(ms.id) as total')
            ->selectRaw('SUM(CASE WHEN ms.mentee_attended = 1 THEN 1 ELSE 0 END) as attended')
            ->selectRaw("SUM(CASE WHEN ms.mentee_attended = 0 OR (ms.status = 'missed' AND ms.mentee_attended IS NULL) THEN 1 ELSE 0 END) as missed")
            ->selectRaw("SUM(CASE WHEN ms.status = 'scheduled' AND ms.scheduled_at > ? THEN 1 ELSE 0 END) as upcoming", [now()])
            ->first();

        $summary = [
            'courses_enrolled' => $enrolments->count(),
            'courses_completed' => $enrolments->where('status', 'completed')->count(),
            'lessons_total' => (int) $lessonStats->sum('lessons_total'),
            'lessons_completed' => (int) $lessonStats->sum('lessons_completed'),
            'time_spent_seconds' => (int) $lessonStats->sum('time_spent_seconds'),
            'assignments_total' => $assessments->count(),
            'assignments_submitted' => $submitted,
            'assignments_graded' => $graded,
            'assignments_pending' => $pending,
            'assignments_overdue' => $overdue,
            'extension_requests_pending' => AssignmentExtensionRequest::query()
                ->where('user_id', $user->id)
                ->whereIn('assessment_id', $assessments->pluck('id'))
                ->where('status', AssignmentExtensionRequest::STATUS_PENDING)
                ->count(),
            'mentorship_sessions_total' => (int) ($mentorship->total ?? 0),
            'mentorship_sessions_attended' => (int) ($mentorship->attended ?? 0),
            'mentorship_sessions_missed' => (int) ($mentorship->missed ?? 0),
            'mentorship_sessions_upcoming' => (int) ($mentorship->upcoming ?? 0),
        ];

        if (Schema::hasTable('event_attendance_records')) {
            $summary['events_attended'] = (int) DB::table('event_attendance_records')
                ->where('user_id', $user->id)
                ->whereIn('attendance_status', ['present', 'late'])
                ->distinct()
                ->count('event_id');
        }

        return response()->json([
            'summary' => $summary,
            'courses' => $courses,
            'recent_activity' => $this->recentActivity($user->id),
        ]);
    }

    private function recentActivity(int $userId): array
    {
        $lessons = DB::table('lesson_progress as lp')
            ->join('lessons as l', 'l.id', '=', 'lp.lesson_id')
            ->join('course_modules as cm', 'cm.id', '=', 'l.course_module_id')
            ->where('lp.user_id', $userId)
            ->whereNotNull('lp.completed_at')
            ->orderByDesc('lp.completed_at')
            ->limit(10)
            ->get(['l.id', 'l.title', 'cm.course_id', 'lp.completed_at as at'])
            ->map(fn ($row) => ['type' => 'lesson_completed'] + (array) $row);

        $submissions = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.user_id', $userId)
            ->whereNotNull('aa.submitted_at')
            ->orderByDesc('aa.submitted_at')
            ->limit(10)
            ->get(['a.id', 'a.title', 'a.course_id', 'aa.submitted_at as at'])
            ->map(fn ($row) => ['type' => 'assignment_submitted'] + (array) $row);

        $sessions = DB::table('mentorship_sessions as ms')
            ->join('mentor_matches as mm', 'mm.id', '=', 'ms.mentor_match_id')
            ->where('mm.mentee_user_id', $userId)
            ->where('ms.mentee_attended', true)
            ->orderByDesc('ms.scheduled_at')
            ->limit(10)
            ->get(['ms.id', 'ms.title', 'ms.scheduled_at as at'])
            ->map(fn ($row) => ['type' => 'session_attended', 'course_id' => null] + (array) $row);

        return $lessons->concat($submissions)->concat($sessions)
            ->map(function (array $row) {
                $at = Carbon::parse($row['at']);

                return [
                    'type' => $row['type'],
                    'title' => $row['title'],
                    'at' => $at->toIso8601String(),
                    'source_id' => (int) $row['id'],
                    'course_id' => $row['course_id'] !== null ? (int) $row['course_id'] : null,
                    '_ts' => $at->getTimestamp(),
                ];
            })
            ->sortByDesc('_ts')
            ->take(10)
            ->map(function (array $row) {
                unset($row['_ts']);

                return $row;
            })
            ->values()
            ->all();
    }
}
