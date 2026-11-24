<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Mail\SystemMail;
use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EnrolmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use ExportsTables;
    private const PARTICIPANT_ROLE_SLUGS = [
        'student',
        'alumni',
        'job-seeker',
        'mentor',
        'employer',
    ];

    public function index(Request $request)
    {
        $query = User::with([
            'roles', 'branches', 'instructedCourses:id,title',
            // Participants: branch lives on the profile, cohorts/courses on enrolments.
            'profile.branch:id,name', 'enrolments.course:id,title',
            'enrolments.course.compulsoryFor:id,title', 'enrolments.course.compulsoryCourses:id,title',
            'enrolments.cohort:id,name',
        ])->latest();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name','like',"%{$search}%")
                    ->orWhere('email','like',"%{$search}%")
                    ->orWhere('phone','like',"%{$search}%")
                    ->orWhere('participant_code','like',"%{$search}%");
            });
        }

        // The dedicated Participants page always filters to participant accounts.
        $forcedType = $request->routeIs('admin.participants.index') ? 'participant' : null;

        if ($type = ($forcedType ?? $request->get('user_type'))) {
            $query->where('user_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($branchId = $request->integer('branch_id')) {
            $this->scopeToBranch($query, $branchId);
        }

        if ($courseId = $request->integer('course_id')) {
            $query->whereHas('enrolments', fn ($q) => $q->where('course_id', $courseId));
        }

        if ($cohortId = $request->integer('cohort_id')) {
            $query->whereHas('enrolments', fn ($q) => $q->where('cohort_id', $cohortId));
        }

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, $forcedType ? 'Participants' : 'Users', $query, [
                'Name' => 'name',
                'Email' => 'email',
                'Phone' => 'phone',
                'Participant code' => 'participant_code',
                'Type' => fn ($u) => ucfirst((string) $u->user_type),
                'Joined Date' => 'created_at',
                'Status' => fn ($u) => ucfirst((string) $u->status),
                'Roles' => fn ($u) => $u->roles->pluck('name')->implode(', '),
                'Branches' => fn ($u) => $u->user_type === 'participant'
                    ? (string) $u->profile?->branch?->name
                    : $u->branches->pluck('name')->implode(', '),
                'Cohorts' => fn ($u) => $u->enrolments->pluck('cohort.name')->filter()->unique()->implode(', '),
                'Courses enrolled' => fn ($u) => $u->enrolments->pluck('course.title')->filter()->implode(', '),
                'Courses taught' => fn ($u) => $u->instructedCourses->pluck('title')->implode(', '),
                'Last login' => 'last_login_at',
                'Registered' => 'created_at',
            ]);
        }

        $perPage = in_array((int) $request->get('per_page'), [10,20,25,50,100], true)
            ? (int) $request->get('per_page')
            : 20;

        $branches = Branch::orderBy('name')->get(['id','name','code','is_active']);

        if ($forcedType === 'participant') {
            [$stats, $branchStats] = $this->participantStats($branches, $branchId ?: null);
            $participantTab = in_array($request->get('tab'), self::PARTICIPANT_TABS, true)
                ? $request->get('tab')
                : self::PARTICIPANT_TABS[0];
            $activity = $participantTab === 'activity'
                ? $this->participantActivity($branchId ?: null, $request->integer('activity_days'))
                : null;
            $signIns = $participantTab === 'signins'
                ? $this->participantSignIns($request, $branchId ?: null)
                : null;
        } else {
            $participantTab = null;
            $activity = null;
            $signIns = null;
            $branchStats = null;
            $stats = [
                'total' => User::count(),
                'participants' => User::where('user_type','participant')->count(),
                'staff' => User::where('user_type','staff')->count(),
                'mentors' => User::where('user_type','mentor')->count(),
                'employers' => User::where('user_type','employer')->count(),
                'inactive' => User::whereIn('status',['inactive','suspended','pending'])->count(),
            ];
        }

        return view('admin.users.index', [
            'forcedType' => $forcedType,
            'users' => $query->paginate($perPage)->withQueryString(),
            'roles' => Role::orderBy('name')->get(),
            'branches' => $branches,
            'courses' => Course::orderBy('title')->get(['id','title','code']),
            'cohorts' => Cohort::orderBy('name')->get(['id','name']),
            'stats' => $stats,
            'branchStats' => $branchStats,
            'activity' => $activity,
            'participantTab' => $participantTab,
            'signIns' => $signIns,
        ]);
    }

    /**
     * A participant belongs to a branch through their profile, or (for older
     * accounts) through the branch pivot. Keep this in one place so the list
     * filter and the statistics cards always agree.
     */
    private function scopeToBranch($query, int $branchId)
    {
        return $query->where(fn ($q) => $q
            ->whereHas('branches', fn ($b) => $b->whereKey($branchId))
            ->orWhereHas('profile', fn ($p) => $p->where('branch_id', $branchId)));
    }

    public const ACTIVITY_RANGES = [7, 30, 90];

    /** Tabs on the Participants page; the first is the default. */
    public const PARTICIPANT_TABS = ['participants', 'activity', 'signins'];

    /** Participant accounts, optionally limited to one branch. */
    private function participantQuery(?int $branchId)
    {
        $query = User::query()->where('user_type', 'participant');

        return $branchId ? $this->scopeToBranch($query, $branchId) : $query;
    }

    /**
     * Every recorded participant sign-in, newest first, for the Sign-ins tab.
     */
    private function participantSignIns(Request $request, ?int $branchId): array
    {
        $participantIds = $this->participantQuery($branchId)->select('users.id');

        $base = fn () => \App\Models\LoginAudit::query()
            ->where('successful', true)
            ->whereIn('user_id', $participantIds);

        $query = $base()->with('user:id,name,email,participant_code')->latest('attempted_at')->latest('id');

        if ($search = trim((string) $request->get('signin_search'))) {
            $query->whereHas('user', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('participant_code', 'like', "%{$search}%")));
        }

        if (in_array($request->get('channel'), ['web', 'app'], true)) {
            $query->where('channel', $request->get('channel'));
        }

        if ($from = $this->validDate($request->get('from'))) {
            $query->where('attempted_at', '>=', $from->startOfDay());
        }

        if ($to = $this->validDate($request->get('to'))) {
            $query->where('attempted_at', '<=', $to->endOfDay());
        }

        $perPage = in_array((int) $request->get('per_page'), [10,20,25,50,100], true)
            ? (int) $request->get('per_page')
            : 25;

        return [
            'items' => $query->paginate($perPage, ['*'], 'signins_page')->withQueryString(),
            'today' => $base()->where('attempted_at', '>=', now()->startOfDay())->count(),
            'week' => $base()->where('attempted_at', '>=', now()->subDays(6)->startOfDay())->count(),
            'people_30' => $base()->where('attempted_at', '>=', now()->subDays(29)->startOfDay())->distinct()->count('user_id'),
        ];
    }

    private function validDate(mixed $value): ?\Illuminate\Support\Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $value);

            return $date->format('Y-m-d') === $value ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Daily participant activity for the Participants page charts. A participant
     * counts as active on a day when they signed in, started or completed a
     * lesson, submitted an assessment, or were marked present/late at a session.
     * Sign-ins are recorded from 24 Nov 2026; earlier days count learning only.
     */
    private function participantActivity(?int $branchId, int $days): array
    {
        $days = in_array($days, self::ACTIVITY_RANGES, true) ? $days : 30;
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();

        $participants = $this->participantQuery($branchId);
        $participantIds = (clone $participants)->select('users.id');

        $dates = collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i)->toDateString());

        // [table, timestamp column, extra constraint]
        $sources = [
            'lessons_started' => ['lesson_progress', 'first_opened_at', null],
            'lessons_completed' => ['lesson_progress', 'completed_at', null],
            'assessments_submitted' => ['assessment_attempts', 'submitted_at', null],
            'attendance' => ['attendance_records', 'created_at', fn ($q) => $q->whereIn('status', ['present', 'late'])],
            'sign_ins' => ['login_audits', 'attempted_at', fn ($q) => $q->where('successful', true)],
        ];

        $totals = [];
        $activeByDay = [];

        foreach ($sources as $key => [$table, $column, $constraint]) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                $totals[$key] = 0;
                continue;
            }

            $rows = \Illuminate\Support\Facades\DB::table($table)
                ->whereIn('user_id', $participantIds)
                ->whereBetween($column, [$start, $end])
                ->when($constraint, $constraint)
                ->selectRaw("user_id, DATE({$column}) as day")
                ->get();

            $totals[$key] = $rows->count();

            foreach ($rows as $row) {
                $activeByDay[$row->day][$row->user_id] = true;
            }
        }

        $registrations = (clone $participants)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $activeInRange = collect($activeByDay)->flatMap(fn ($users) => array_keys($users))->unique()->count();

        $now = now();
        $recency = [
            ['label' => 'Last 7 days', 'count' => (clone $participants)->where('last_login_at', '>=', $now->copy()->subDays(7))->count()],
            ['label' => '8–30 days ago', 'count' => (clone $participants)->whereBetween('last_login_at', [$now->copy()->subDays(30), $now->copy()->subDays(7)])->count()],
            ['label' => '31–90 days ago', 'count' => (clone $participants)->whereBetween('last_login_at', [$now->copy()->subDays(90), $now->copy()->subDays(30)])->count()],
            ['label' => 'Over 90 days ago', 'count' => (clone $participants)->where('last_login_at', '<', $now->copy()->subDays(90))->count()],
            ['label' => 'Never signed in', 'count' => (clone $participants)->whereNull('last_login_at')->count()],
        ];

        return [
            'days' => $days,
            'ranges' => self::ACTIVITY_RANGES,
            'from' => $start,
            'to' => $end,
            'series' => $dates->map(fn ($day) => [
                'date' => $day,
                'active' => count($activeByDay[$day] ?? []),
                'registrations' => (int) ($registrations[$day] ?? 0),
            ])->values()->all(),
            'totals' => $totals + ['active_participants' => $activeInRange],
            'recency' => $recency,
        ];
    }

    /**
     * Statistics for the Participants page. The headline cards follow the
     * branch filter; the branch cards always show every branch's total.
     */
    private function participantStats($branches, ?int $branchId): array
    {
        $base = function () use ($branchId) {
            $q = User::where('user_type', 'participant');
            return $branchId ? $this->scopeToBranch($q, $branchId) : $q;
        };

        $now = now();

        $stats = [
            'total' => $base()->count(),
            'today' => $base()->whereDate('created_at', $now->toDateString())->count(),
            'month' => $base()->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->count(),
            'year' => $base()->whereBetween('created_at', [$now->copy()->startOfYear(), $now->copy()->endOfYear()])->count(),
            'active' => $base()->where('status', 'active')->count(),
            'inactive' => $base()->whereIn('status', ['inactive','suspended','pending'])->count(),
        ];

        $branchStats = [
            'all' => User::where('user_type', 'participant')->count(),
            'items' => $branches->map(fn ($branch) => [
                'branch' => $branch,
                'count' => $this->scopeToBranch(User::where('user_type', 'participant'), $branch->id)->count(),
            ]),
        ];

        return [$stats, $branchStats];
    }

    public function bulkDestroy(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','exists:users,id'],
        ]);

        $users = User::query()
            ->whereKey($data['ids'])
            ->where('id', '!=', auth()->id())
            ->get()
            // Super administrator accounts can never be removed in bulk.
            ->reject(fn (User $user) => $user->isSuperAdmin());

        foreach ($users as $user) {
            $old = $user->load('roles')->toArray();
            $user->delete();
            $audit->log('users', 'deleted', null, $old, []);
        }

        $count = $users->count();

        return back()->with(
            $count > 0 ? 'success' : 'error',
            $count > 0
                ? $count.' user(s) deleted.'
                : 'No users were deleted.'
        );
    }

    /**
     * Put the selected users in a branch. A participant has one branch, so they
     * are moved; staff, mentors and employers can have several, so the branch
     * is added to the ones they already have. Enrolments are left unchanged.
     */
    public function bulkBranch(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','exists:users,id'],
            'branch_id' => ['required','integer','exists:branches,id'],
        ]);

        $branch = Branch::findOrFail($data['branch_id']);
        $users = User::query()->whereKey($data['ids'])->with(['profile', 'branches:id'])->get();
        $changed = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($users, $branch, $audit, &$changed) {
            foreach ($users as $user) {
                $oldBranchIds = $user->branches->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                $oldProfileBranch = $user->profile?->branch_id ? (int) $user->profile->branch_id : null;

                if ($user->isParticipant()) {
                    if ($oldProfileBranch === (int) $branch->id && $oldBranchIds === [(int) $branch->id]) {
                        continue;
                    }
                    $user->profile()->updateOrCreate(['user_id' => $user->id], ['branch_id' => $branch->id]);
                    $user->branches()->sync([$branch->id]);
                } else {
                    if (in_array((int) $branch->id, $oldBranchIds, true)) {
                        continue;
                    }
                    $user->branches()->syncWithoutDetaching([$branch->id]);
                }

                $changed++;
                $audit->log('users', 'branch_updated', $user,
                    ['branches' => $oldBranchIds, 'profile_branch_id' => $oldProfileBranch],
                    ['branches' => $user->branches()->pluck('branches.id')->all(), 'profile_branch_id' => $user->profile()->value('branch_id')]
                );
            }
        });

        if ($changed === 0) {
            return back()->with('error', 'No users were changed. They are already in '.$branch->name.'.');
        }

        $skipped = $users->count() - $changed;

        return back()->with('success', $changed.' user(s) assigned to '.$branch->name.'.'
            .($skipped > 0 ? ' '.$skipped.' already there.' : ''));
    }

    public function bulkStatus(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','exists:users,id'],
            'status' => ['required','in:active,inactive,suspended,pending'],
        ]);

        $candidates = User::query()->whereKey($data['ids'])->get();

        // An administrator cannot lock themselves out, and super administrator
        // accounts can only be deactivated one at a time from the edit form.
        $users = $data['status'] === 'active'
            ? $candidates
            : $candidates->reject(fn (User $user) =>
                (int) $user->id === (int) auth()->id() || $user->isSuperAdmin());

        $users = $users->reject(fn (User $user) => $user->status === $data['status']);

        foreach ($users as $user) {
            $old = $user->only(['id','status']);
            $user->update(['status' => $data['status']]);
            $audit->log('users', 'status_updated', $user, $old, $user->only(['id','status']));
        }

        $count = $users->count();
        $skipped = $candidates->count() - $count;
        $label = ucfirst($data['status']);

        if ($count === 0) {
            return back()->with('error', 'No users were changed. They are already '.$label.' or cannot be changed in bulk.');
        }

        return back()->with('success', $count.' user(s) set to '.$label.'.'
            .($skipped > 0 ? ' '.$skipped.' skipped (already '.$label.', your own account or a super administrator).' : ''));
    }

    public function create()
    {
        return redirect()
            ->route('admin.users.index')
            ->with('open_user_modal','create');
    }

    public function store(Request $request, AuditService $audit, EnrolmentService $enrolments)
    {
        $data = $this->validated($request);
        $roleIds = $this->validatedRoleIds($data['user_type'], $data['roles'] ?? []);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'user_type' => $data['user_type'],
            'status' => $data['status'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => $data['user_type'] === 'staff' ? null : now(),
        ]);

        $user->roles()->sync($roleIds);
        $this->syncAssignments($user, $data, $request->boolean('sync_assignments'), $enrolments);

        if ($user->isStaff()) {
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $user->getKey(),
                    'hash' => sha1($user->getEmailForVerification()),
                ]
            );

            app(\App\Services\NotificationDispatcher::class)->notify(
                $user,
                'account',
                'Your staff account is ready',
                'Your staff login email is '.$user->email.'. Verify your email before signing in to the staff portal.',
                '/admin/login',
                ['user_id' => $user->id],
                false
            );

            try {
                Mail::to($user->email)->send(new SystemMail(
                    subjectLine: 'Your '.config('app.name', 'ElevateHer360').' staff login details',
                    heading: 'Welcome to '.config('app.name', 'ElevateHer360'),
                    lines: [
                        'Your staff login email is: '.$user->email,
                        'Your password is: '.$data['password'],
                        'After verifying your email, sign in at '.route('admin.login').'.',
                    ],
                    actionUrl: $verificationUrl,
                    actionLabel: 'Verify your email',
                    greeting: 'Hello '.strtok((string) $user->name, ' '),
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        } else {
            app(\App\Services\NotificationDispatcher::class)->notify(
                $user,
                'account',
                'Welcome to '.config('app.name', 'ElevateHer360'),
                'Your account has been created. Sign in with your email address and the password provided to you.',
                '/login',
                ['user_id' => $user->id]
            );
        }

        $audit->log(
            'users',
            'created',
            $user,
            [],
            $user->load('roles')->toArray()
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success','User created.');
    }

    public function edit(User $user)
    {
        return redirect()
            ->route('admin.users.index')
            ->with('open_user_modal','edit-'.$user->id);
    }

    public function syncParticipantCompulsory(User $user, EnrolmentService $enrolments)
    {
        abort_unless($user->isParticipant(), 404);

        $user->load(['enrolments.course.compulsoryFor', 'enrolments.course.compulsoryCourses']);
        $coreEnrolments = $user->enrolments->filter(fn ($enrolment) =>
            $enrolment->course
            && ! in_array($enrolment->status, EnrolmentService::INACTIVE_STATUSES, true)
            && $enrolment->course->compulsoryFor->isEmpty()
        );

        if ($coreEnrolments->isEmpty()) {
            return back()->with('info', 'This participant has no active core course to check.');
        }

        $before = $user->enrolments()->count();
        \Illuminate\Support\Facades\DB::transaction(function () use ($coreEnrolments, $user, $enrolments) {
            foreach ($coreEnrolments as $coreEnrolment) {
                $enrolments->enrolCompulsory($user, $coreEnrolment->course, $coreEnrolment->cohort_id);
            }
        });
        $created = max(0, $user->enrolments()->count() - $before);

        return back()->with('success', $created
            ? $created.' missing compulsory course enrolment(s) added for '.$user->name.'.'
            : 'All compulsory courses are already assigned to '.$user->name.'.');
    }

    public function update(Request $request, User $user, AuditService $audit, EnrolmentService $enrolments)
    {
        $old = $user->load(['roles', 'branches', 'instructedCourses'])->toArray();
        $data = $this->validated($request, $user);
        $roleIds = $this->validatedRoleIds($data['user_type'], $data['roles'] ?? []);

        if (
            $user->isSuperAdmin()
            && (int) $user->id === (int) auth()->id()
            && $data['status'] !== 'active'
        ) {
            throw ValidationException::withMessages([
                'status' => 'You cannot deactivate or suspend your own Super Administrator account.',
            ]);
        }

        $user->update([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'user_type' => $data['user_type'],
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $user->update([
                'password' => Hash::make($data['password']),
            ]);
        }

        $oldRoleIds = collect($old['roles'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $newRoleIds = collect($roleIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

        $user->roles()->sync($roleIds);
        $this->syncAssignments($user, $data, $request->boolean('sync_assignments'), $enrolments);

        if ($oldRoleIds !== $newRoleIds) {
            app(\App\Services\NotificationDispatcher::class)->notify(
                $user,
                'account_roles',
                'Your account roles were updated',
                'An administrator changed the roles assigned to your account. Review the areas you can now access.',
                '/notifications',
                ['user_id' => $user->id]
            );
        }

        $audit->log(
            'users',
            'updated',
            $user,
            $old,
            $user->fresh(['roles', 'branches', 'instructedCourses'])->toArray()
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success','User updated.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users','email')->ignore($user?->id),
            ],
            'phone' => ['nullable','string','max:30'],
            'user_type' => ['required', Rule::in(['participant', 'staff', 'mentor', 'employer'])],
            'status' => ['required','in:active,inactive,suspended,pending'],
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'roles' => ['nullable','array'],
            'roles.*' => ['integer','exists:roles,id'],
            'branches' => ['nullable','array'],
            'branches.*' => ['integer','exists:branches,id'],
            'courses' => ['nullable','array'],
            'courses.*' => ['integer','exists:courses,id'],
        ]);

        if ($data['user_type'] === 'participant' && count($data['branches'] ?? []) > 1) {
            throw ValidationException::withMessages(['branches' => 'A participant can be assigned to one profile branch.']);
        }

        return $data;
    }

    /**
     * Persist the branch/course selections whenever the assignments tab is submitted.
     * The user type controls which areas can act on those associations; changing a
     * user's type must not silently discard the administrator's saved selections.
     */
    private function syncAssignments(User $user, array $data, bool $submitted, EnrolmentService $enrolments): void
    {
        // Forms without the Assignments tab must not wipe existing assignments.
        if (! $submitted) {
            return;
        }

        $branchIds = array_values(array_unique(array_map('intval', $data['branches'] ?? [])));
        $user->branches()->sync($branchIds);

        // Keep the lead-instructor flag on courses the user was already assigned to.
        $leads = $user->instructedCourses()->wherePivot('is_lead', true)->pluck('courses.id')->all();

        $user->instructedCourses()->sync(collect($data['courses'] ?? [])
            ->mapWithKeys(fn ($courseId) => [(int) $courseId => ['is_lead' => in_array((int) $courseId, $leads, true)]])
            ->all());

        // The user editor is also the admin's quick assignment surface for participants:
        // keep their operational branch and course enrolments in sync with those selections.
        if ($user->isParticipant()) {
            $branchId = $branchIds[0] ?? null;
            $user->profile()->updateOrCreate(['user_id' => $user->id], ['branch_id' => $branchId]);

            foreach (Course::query()->whereIn('id', $data['courses'] ?? [])->get() as $course) {
                $cohort = $course->cohorts()
                    ->when($branchId, fn ($query) => $query->where(fn ($q) => $q->where('cohorts.branch_id', $branchId)
                        ->orWhereHas('branches', fn ($branches) => $branches->where('branches.id', $branchId))))
                    ->orderBy('cohorts.id')
                    ->first();

                $enrolments->enrol($user, $course, ['cohort_id' => $cohort?->id]);
            }
        }
    }

    private function validatedRoleIds(string $userType, array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        $roles = Role::query()
            ->whereIn('id', $roleIds)
            ->get(['id','slug','name']);

        if ($roles->count() !== count(array_unique($roleIds))) {
            throw ValidationException::withMessages([
                'roles' => 'One or more selected roles do not exist.',
            ]);
        }

        foreach ($roles as $role) {
            $isParticipantRole = in_array(
                $role->slug,
                self::PARTICIPANT_ROLE_SLUGS,
                true
            );

            if ($userType !== 'staff' && ! $isParticipantRole) {
                throw ValidationException::withMessages([
                    'roles' => "The role '{$role->name}' is a staff/system role and cannot be assigned to a {$userType} account.",
                ]);
            }

            if ($userType === 'staff' && $isParticipantRole) {
                throw ValidationException::withMessages([
                    'roles' => "The role '{$role->name}' is a partner/participant role and cannot be assigned to a staff account.",
                ]);
            }
        }

        return $roles->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
