<?php

namespace App\Services\HR;

use App\Models\StaffDocument;
use App\Models\HrDocumentReminder;
use App\Models\HrDocumentRequirement;
use App\Models\HrPolicy;
use App\Models\HrPolicyAssignment;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Staff HR compliance: policies shared with staff (read + acknowledge) and the
 * documents every staff member must submit. Also sends reminders to staff with
 * outstanding items, manually from HR or automatically on a schedule.
 */
class HrComplianceService
{
    /** Automatic reminders are not repeated more often than this. */
    public const AUTO_REMINDER_COOLDOWN_DAYS = 3;

    public function __construct(private readonly NotificationDispatcher $notifier)
    {
    }

    /** Active staff accounts that HR compliance applies to. */
    public function staffQuery(): Builder
    {
        return User::query()->where('user_type', 'staff')->where('status', 'active');
    }

    /**
     * Share a policy with staff. Existing assignments are kept (and not re-notified).
     *
     * @param  iterable<int>  $userIds
     */
    public function assignPolicy(HrPolicy $policy, iterable $userIds, ?User $actor = null, bool $notify = true): int
    {
        $created = 0;
        $due = $policy->requires_acknowledgement ? now()->addDays(max(1, (int) $policy->due_days))->toDateString() : null;

        foreach (collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique() as $userId) {
            $assignment = HrPolicyAssignment::firstOrCreate(
                ['policy_id' => $policy->id, 'user_id' => $userId],
                ['assigned_by' => $actor?->id, 'due_date' => $due],
            );
            if (! $assignment->wasRecentlyCreated) {
                continue;
            }
            $created++;

            if ($notify && ($user = User::find($userId))) {
                $this->notifier->notify(
                    $user,
                    'hr_policy_shared',
                    'New policy to read: '.$policy->title,
                    $policy->requires_acknowledgement
                        ? 'Please read the '.$policy->title.' policy and confirm you have understood it'.($due ? ' by '.now()->parse($due)->format('d M Y') : '').'.'
                        : 'HR has shared the '.$policy->title.' policy with you.',
                    route('staff.hr.policies.show', $policy),
                );
            }
        }

        return $created;
    }

    /** Give a newly added staff member every policy flagged for new staff. */
    public function onboard(User $user, ?User $actor = null): int
    {
        $count = 0;
        HrPolicy::where('is_active', true)->where('assign_to_new_staff', true)->get()
            ->each(function (HrPolicy $policy) use ($user, $actor, &$count) {
                $count += $this->assignPolicy($policy, [$user->id], $actor);
            });

        return $count;
    }

    /** Policy assignments the user still has to acknowledge. */
    public function outstandingPolicies(User $user): Collection
    {
        return HrPolicyAssignment::with('policy')
            ->where('user_id', $user->id)
            ->whereNull('acknowledged_at')
            ->whereHas('policy', fn ($q) => $q->where('is_active', true)->where('requires_acknowledgement', true))
            ->get();
    }

    /** Mandatory document requirements the user has no submitted or approved document for. */
    public function missingDocuments(User $user): Collection
    {
        $covered = StaffDocument::where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'approved'])
            ->whereNotNull('requirement_id')
            ->pluck('requirement_id')->all();

        return HrDocumentRequirement::where('is_active', true)->where('is_mandatory', true)
            ->whereNotIn('id', $covered)->orderBy('position')->orderBy('name')->get();
    }

    /**
     * Compliance summary for many staff at once (two queries, no N+1).
     *
     * @return Collection<int, array{user:User, policies:int, documents:int, missing:array<int,string>, pending_policies:array<int,string>}>
     */
    public function summary(?Collection $users = null): Collection
    {
        $users ??= $this->staffQuery()->orderBy('name')->get();
        $ids = $users->pluck('id');

        $requirements = HrDocumentRequirement::where('is_active', true)->where('is_mandatory', true)->orderBy('position')->get();
        $covered = StaffDocument::whereIn('user_id', $ids)->whereIn('status', ['submitted', 'approved'])
            ->whereNotNull('requirement_id')->get(['user_id', 'requirement_id'])
            ->groupBy('user_id')->map(fn ($rows) => $rows->pluck('requirement_id')->all());

        $pending = HrPolicyAssignment::with('policy:id,title')
            ->whereIn('user_id', $ids)->whereNull('acknowledged_at')
            ->whereHas('policy', fn ($q) => $q->where('is_active', true)->where('requires_acknowledgement', true))
            ->get()->groupBy('user_id');

        return $users->map(function (User $user) use ($requirements, $covered, $pending) {
            $have = $covered[$user->id] ?? [];
            $missing = $requirements->reject(fn ($r) => in_array($r->id, $have, true))->pluck('name')->values()->all();
            $policies = ($pending[$user->id] ?? collect())->map(fn ($a) => $a->policy?->title)->filter()->values()->all();

            return [
                'user' => $user,
                'documents' => count($missing),
                'policies' => count($policies),
                'missing' => $missing,
                'pending_policies' => $policies,
            ];
        });
    }

    /**
     * Remind staff about missing documents and unacknowledged policies.
     *
     * @param  iterable<int>|null  $userIds  null = every active staff member
     * @param  string  $scope  all | documents | policies
     * @return int number of staff reminded
     */
    public function sendReminders(?iterable $userIds = null, string $scope = 'all', bool $respectCooldown = false, ?string $note = null): int
    {
        $users = $userIds === null
            ? $this->staffQuery()->get()
            : $this->staffQuery()->whereIn('id', collect($userIds)->map(fn ($id) => (int) $id)->all())->get();

        $cutoff = now()->subDays(self::AUTO_REMINDER_COOLDOWN_DAYS);
        $sent = 0;

        foreach ($this->summary($users) as $row) {
            /** @var User $user */
            $user = $row['user'];
            $lines = [];

            $docs = in_array($scope, ['all', 'documents'], true) ? $row['missing'] : [];
            $pols = in_array($scope, ['all', 'policies'], true) ? $row['pending_policies'] : [];

            if ($respectCooldown) {
                $docReminder = HrDocumentReminder::firstWhere('user_id', $user->id);
                if ($docReminder?->last_reminded_at && $docReminder->last_reminded_at->gt($cutoff)) {
                    $docs = [];
                }
                if ($pols) {
                    $recent = HrPolicyAssignment::where('user_id', $user->id)->whereNull('acknowledged_at')
                        ->where('last_reminded_at', '>', $cutoff)->exists();
                    if ($recent) $pols = [];
                }
            }

            if ($docs) $lines[] = 'Documents to upload: '.implode(', ', $docs).'.';
            if ($pols) $lines[] = 'Policies to read and acknowledge: '.implode(', ', $pols).'.';
            if (! $lines) continue;
            if ($note) array_unshift($lines, trim($note));

            $this->notifier->notify(
                $user,
                'hr_compliance_reminder',
                'Reminder: outstanding HR items',
                implode(' ', $lines),
                route('staff.hr.index'),
            );

            if ($docs) {
                $reminder = HrDocumentReminder::firstOrNew(['user_id' => $user->id]);
                $reminder->reminders_sent = (int) $reminder->reminders_sent + 1;
                $reminder->last_reminded_at = now();
                $reminder->save();
            }
            if ($pols) {
                HrPolicyAssignment::where('user_id', $user->id)->whereNull('acknowledged_at')
                    ->update(['last_reminded_at' => now(), 'reminders_sent' => \Illuminate\Support\Facades\DB::raw('reminders_sent + 1')]);
            }
            $sent++;
        }

        return $sent;
    }
}
