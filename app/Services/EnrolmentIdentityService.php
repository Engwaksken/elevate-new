<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\CourseApplication;
use App\Models\CourseCall;
use App\Models\Enrolment;
use App\Models\Profile;
use App\Models\Programme;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnrolmentIdentityService
{
    public function allocate(Enrolment $enrolment): string
    {
        $prefix = $this->prefixFor($enrolment);

        // Persist the counter separately from enrolment rows so deleted IDs are never reused.
        // The unique prefix and row lock serialize allocations from concurrent requests.
        return DB::transaction(function () use ($prefix) {
            return $prefix.'/'.str_pad((string) $this->nextNumber($prefix), 3, '0', STR_PAD_LEFT);
        }, 5);
    }

    /**
     * Re-derive the identity codes for every enrolment sourced from a course
     * call, e.g. after an admin changes the call's programme or project.
     */
    public function resyncForCall(CourseCall $call): int
    {
        $applicationIds = $call->applications()->pluck('id');

        if ($applicationIds->isEmpty()) {
            return 0;
        }

        $enrolments = Enrolment::query()
            ->where('source_type', 'application')
            ->whereIn('source_id', $applicationIds)
            ->get();

        $updated = 0;

        foreach ($enrolments as $enrolment) {
            $prefix = $this->prefixFor($enrolment);

            if (str_starts_with((string) $enrolment->enrolment_code, $prefix.'/')) {
                continue;
            }

            $newCode = DB::transaction(
                fn () => $prefix.'/'.str_pad((string) $this->nextNumber($prefix), 3, '0', STR_PAD_LEFT),
                5
            );

            $oldCode = $enrolment->enrolment_code;

            $enrolment->forceFill(['enrolment_code' => $newCode])->saveQuietly();

            $user = User::find($enrolment->user_id);

            if ($user && $user->participant_code === $oldCode) {
                $user->forceFill(['participant_code' => $newCode])->saveQuietly();
            }

            $updated++;
        }

        return $updated;
    }

    public function resyncForProgramme(int $programmeId): int
    {
        $projectIds = Project::where('programme_id', $programmeId)->pluck('id');

        $calls = CourseCall::query()
            ->where('programme_id', $programmeId)
            ->orWhereIn('project_id', $projectIds)
            ->get();

        return $this->resyncCalls($calls);
    }

    public function resyncForProject(int $projectId): int
    {
        return $this->resyncCalls(CourseCall::where('project_id', $projectId)->get());
    }

    private function resyncCalls(iterable $calls): int
    {
        $updated = 0;

        foreach ($calls as $call) {
            $updated += $this->resyncForCall($call);
        }

        return $updated;
    }

    public function prefixFor(Enrolment $enrolment): string
    {
        $course = Course::withTrashed()->find($enrolment->course_id);

        // When the enrolment came from a course/opportunity call, the call's
        // programme or project determines the participant ID prefix.
        $call = null;

        if (($enrolment->source_type ?? null) === 'application' && $enrolment->source_id) {
            $call = CourseApplication::with('courseCall')->find($enrolment->source_id)?->courseCall;
        }

        $cohort = Cohort::find($enrolment->cohort_id) ?: ($call?->cohort_id ? Cohort::find($call->cohort_id) : null);
        $project = Project::find($cohort?->project_id ?? $call?->project_id ?? $course?->project_id);
        $programme = Programme::find($cohort?->programme_id ?? $call?->programme_id ?? $course?->programme_id ?? $project?->programme_id);
        $profileBranch = Profile::where('user_id', $enrolment->user_id)->value('branch_id');
        $participantBranch = $profileBranch && $course?->branches()->whereKey($profileBranch)->exists() ? $profileBranch : null;
        $branchId = $cohort?->branch_id ?? $participantBranch ?? $course?->branch_id ?? $profileBranch;
        $branch = Branch::find($branchId);
        $year = ($enrolment->enrolled_at ?? $enrolment->created_at ?? now())->format('y');

        return implode('/', [
            $this->segment($project?->code ?: $programme?->code, 'PRG'),
            $this->segment($branch?->code, 'BR'),
            $this->courseSegment($course),
            $year,
        ]);
    }

    private function nextNumber(string $prefix): int
    {
        DB::table('enrolment_identity_sequences')->insertOrIgnore(['prefix' => $prefix, 'last_number' => 0]);
        $sequence = DB::table('enrolment_identity_sequences')->where('prefix', $prefix)->lockForUpdate()->first();
        $number = $sequence->last_number + 1;
        DB::table('enrolment_identity_sequences')->where('prefix', $prefix)->update(['last_number' => $number]);

        return $number;
    }

    public function assignParticipantCode(Enrolment $enrolment): void
    {
        DB::transaction(function () use ($enrolment) {
            $user = User::whereKey($enrolment->user_id)->lockForUpdate()->first();
            if ($user?->isParticipant() && (! $user->participant_code || preg_match('/^EH\d{2}-\d+$/', $user->participant_code))) {
                $user->participant_code = $enrolment->enrolment_code;
                $user->saveQuietly();
            }
        }, 5);
    }

    private function segment(?string $code, string $fallback): string
    {
        return substr(preg_replace('/[^A-Z0-9_-]/', '', strtoupper(trim($code ?? ''))), 0, 40) ?: $fallback;
    }

    public function cohortSegment(?string $code): string
    {
        $segment = $this->segment($code, '0');
        if (preg_match('/^(?:C(?:OHORT)?[-_]?)?0*(\d+)$/', $segment, $matches)) {
            return 'C'.(ltrim($matches[1], '0') ?: '0');
        }
        return str_starts_with($segment, 'C') ? $segment : 'C'.$segment;
    }

    public function courseSegment(?Course $course): string
    {
        return 'C'.($course?->id ?? 0);
    }

    public function normalizeCode(string $code): string
    {
        $parts = explode('/', $code);
        if (count($parts) !== 5 || ! ctype_digit($parts[4])) return $code;
        $parts[2] = $this->cohortSegment($parts[2]);
        $parts[4] = str_pad($parts[4], 3, '0', STR_PAD_LEFT);
        return implode('/', $parts);
    }
}
