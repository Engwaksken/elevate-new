<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Course;
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
        $course = Course::withTrashed()->find($enrolment->course_id);
        $cohort = Cohort::find($enrolment->cohort_id);
        $project = Project::find($cohort?->project_id ?? $course?->project_id);
        $programme = Programme::find($cohort?->programme_id ?? $course?->programme_id ?? $project?->programme_id);
        $profileBranch = Profile::where('user_id', $enrolment->user_id)->value('branch_id');
        $participantBranch = $profileBranch && $course?->branches()->whereKey($profileBranch)->exists() ? $profileBranch : null;
        $branchId = $cohort?->branch_id ?? $participantBranch ?? $course?->branch_id ?? $profileBranch;
        $branch = Branch::find($branchId);
        $year = ($enrolment->enrolled_at ?? $enrolment->created_at ?? now())->format('y');
        $prefix = implode('/', [
            $this->segment($project?->code ?: $programme?->code, 'PRG'),
            $this->segment($branch?->code, 'BR'),
            $this->courseSegment($course),
            $year,
        ]);

        // Persist the counter separately from enrolment rows so deleted IDs are never reused.
        // The unique prefix and row lock serialize allocations from concurrent requests.
        return DB::transaction(function () use ($prefix) {
            DB::table('enrolment_identity_sequences')->insertOrIgnore(['prefix' => $prefix, 'last_number' => 0]);
            $sequence = DB::table('enrolment_identity_sequences')->where('prefix', $prefix)->lockForUpdate()->first();
            $number = $sequence->last_number + 1;
            DB::table('enrolment_identity_sequences')->where('prefix', $prefix)->update(['last_number' => $number]);

            return $prefix.'/'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
        }, 5);
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
