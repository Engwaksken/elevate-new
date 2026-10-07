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
        $query = User::with(['roles', 'branches', 'instructedCourses:id,title'])->latest();

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
            $query->where(fn ($q) => $q
                ->whereHas('branches', fn ($b) => $b->whereKey($branchId))
                ->orWhereHas('profile', fn ($p) => $p->where('branch_id', $branchId)));
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
                'Status' => fn ($u) => ucfirst((string) $u->status),
                'Roles' => fn ($u) => $u->roles->pluck('name')->implode(', '),
                'Branches' => fn ($u) => $u->branches->pluck('name')->implode(', '),
                'Courses taught' => fn ($u) => $u->instructedCourses->pluck('title')->implode(', '),
                'Last login' => 'last_login_at',
                'Registered' => 'created_at',
            ]);
        }

        $perPage = in_array((int) $request->get('per_page'), [10,20,25,50,100], true)
            ? (int) $request->get('per_page')
            : 20;

        return view('admin.users.index', [
            'forcedType' => $forcedType,
            'users' => $query->paginate($perPage)->withQueryString(),
            'roles' => Role::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(['id','name','code','is_active']),
            'courses' => Course::orderBy('title')->get(['id','title','code']),
            'cohorts' => Cohort::orderBy('name')->get(['id','name']),
            'stats' => [
                'total' => User::count(),
                'participants' => User::where('user_type','participant')->count(),
                'staff' => User::where('user_type','staff')->count(),
                'inactive' => User::whereIn('status',['inactive','suspended','pending'])->count(),
            ],
        ]);
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

    public function create()
    {
        return redirect()
            ->route('admin.users.index')
            ->with('open_user_modal','create');
    }

    public function store(Request $request, AuditService $audit)
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
        $this->syncAssignments($user, $data, $request->boolean('sync_assignments'));

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

    public function update(Request $request, User $user, AuditService $audit)
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
        $this->syncAssignments($user, $data, $request->boolean('sync_assignments'));

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
        return $request->validate([
            'name' => ['required','string','max:190'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users','email')->ignore($user?->id),
            ],
            'phone' => ['nullable','string','max:30'],
            'user_type' => ['required','in:participant,staff'],
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
    }

    /**
     * Staff (instructors, trainers) can work across several branches and courses.
     * Participant accounts keep their single branch on the profile and teach no courses.
     */
    private function syncAssignments(User $user, array $data, bool $submitted): void
    {
        // Forms without the Assignments tab must not wipe existing assignments.
        if (! $submitted && $data['user_type'] === 'staff') {
            return;
        }

        if ($data['user_type'] !== 'staff') {
            $user->branches()->sync([]);
            $user->instructedCourses()->sync([]);

            return;
        }

        $user->branches()->sync($data['branches'] ?? []);

        // Keep the lead-instructor flag on courses the user was already assigned to.
        $leads = $user->instructedCourses()->wherePivot('is_lead', true)->pluck('courses.id')->all();

        $user->instructedCourses()->sync(collect($data['courses'] ?? [])
            ->mapWithKeys(fn ($courseId) => [(int) $courseId => ['is_lead' => in_array((int) $courseId, $leads, true)]])
            ->all());
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

            if ($userType === 'participant' && ! $isParticipantRole) {
                throw ValidationException::withMessages([
                    'roles' => "The role '{$role->name}' is a staff/system role and cannot be assigned to a participant account.",
                ]);
            }

            if ($userType === 'staff' && $isParticipantRole) {
                throw ValidationException::withMessages([
                    'roles' => "The role '{$role->name}' is a participant role and cannot be assigned to a staff account.",
                ]);
            }
        }

        return $roles->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
