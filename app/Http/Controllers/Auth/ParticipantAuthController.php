<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ParticipantRegisterRequest;
use App\Models\Branch;
use App\Models\Consent;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ParticipantAuthController extends Controller
{
    /**
     * Display the participant registration form.
     */
    public function create(): View
    {
        return view('auth.register', [
            'branches' => Branch::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Register a new participant.
     */
    public function store(
        ParticipantRegisterRequest $request,
        AuditService $audit
    ): RedirectResponse {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim(
                    ($data['given_name'] ?? '')
                    . ' '
                    . ($data['surname'] ?? '')
                ),
                'email' => strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? null,
                'user_type' => 'participant',
                'status' => 'pending',
                'password' => Hash::make($data['password']),
            ]);

            $user->profile()->create([
                'branch_id' => $data['branch_id'] ?? null,
                'surname' => $data['surname'],
                'given_name' => $data['given_name'],
                'other_name' => $data['other_name'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'country' => $data['country'] ?? null,
                'district' => $data['district'] ?? null,
                'location' => $data['location'] ?? null,
                'is_pwd' => (bool) ($data['is_pwd'] ?? false),
                'education_level' => $data['education_level'] ?? null,
                'employment_status' => $data['employment_status'] ?? null,
                'career_interests' => $data['career_interests'] ?? null,
                'preferred_language' => $data['preferred_language'] ?? 'English',
            ]);

            /*
             * Every newly registered participant gets the Student role.
             */
            $studentRole = Role::where('slug', 'student')->first();

            if ($studentRole) {
                $user->roles()->syncWithoutDetaching([
                    $studentRole->id,
                ]);
            }

            foreach (['privacy_policy', 'terms'] as $type) {
                Consent::create([
                    'user_id' => $user->id,
                    'consent_type' => $type,
                    'policy_version' => config(
                        'app.policy_version',
                        '1.0'
                    ),
                    'accepted' => true,
                    'accepted_at' => now(),
                    'ip_address' => request()->ip(),
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        $audit->log(
            'auth',
            'participant_registered',
            $user
        );

        $this->notifyRegistration($user);

        return redirect()
            ->route('verification.notice')
            ->with(
                'success',
                'Account created. Please verify your email address.'
            );
    }

    /**
     * Welcome the new participant and tell the registration team.
     */
    private function notifyRegistration(User $user): void
    {
        try {
            $dispatcher = app(\App\Services\NotificationDispatcher::class);

            $dispatcher->notify(
                $user,
                'account',
                'Welcome to ElevateHer360',
                'Your participant account is ready. Complete your profile, explore courses and connect with a mentor.',
                '/dashboard',
                ['user_id' => $user->id]
            );

            $staff = User::query()
                ->where('user_type', 'staff')
                ->where('status', 'active')
                ->whereHas('roles.permissions', fn ($query) => $query->where('slug', 'users.view'))
                ->get()
                ->reject(fn (User $member) => (int) $member->id === (int) $user->id);

            $dispatcher->notifyMany(
                $staff,
                'registration',
                'New participant registration',
                "{$user->name} ({$user->email}) just registered on ElevateHer360.",
                '/admin/users',
                ['user_id' => $user->id]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Display the participant login form.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate a participant.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email' => strtolower(
                trim((string) $request->input('email'))
            ),
            'password' => (string) $request->input('password'),
        ];

        if (
            ! Auth::attempt(
                $credentials,
                $request->boolean('remember')
            )
        ) {
            return back()
                ->withErrors([
                    'email' => 'Invalid email or password.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'Unable to sign in. Please try again.',
                ])
                ->onlyInput('email');
        }

        /*
         * Participant roles.
         *
         * These roles are allowed to use the participant portal.
         */
        $participantRoles = [
            'student',
            'alumni',
            'job-seeker',

            'Student',
            'Alumni',
            'Job Seeker',
        ];

        /*
         * Staff/admin roles.
         *
         * Accounts with these roles must not use the participant portal.
         */
        $staffRoles = [
            'super-administrator',
            'super-admin',
            'administrator',

            'programs-lead',
            'program-manager',
            'program-officer',

            'meal-lead',
            'me-officer',

            'operations-lead',
            'operations-officer',

            'instructor',
            'trainer',

            'mentorship-coordinator',
            'career-coach',
            'placement-officer',
            'library-administrator',

            'hr',
            'procurement-officer',
            'asset-stores-officer',
            'finance',

            'consultant',
            'viewer',
            'mentor',
            'employer',

            'Super Administrator',
            'Super Admin',
            'Administrator',

            'Programs Lead',
            'Program Manager',
            'Program Officer',

            'MEAL Lead',
            'M&E Officer',

            'Operations Lead',
            'Operations Officer',

            'Instructor',
            'Trainer',

            'Mentorship Coordinator',
            'Career Coach',
            'Placement Officer',
            'Library Administrator',

            'HR',
            'Procurement Officer',
            'Asset/Stores Officer',
            'Finance',

            'Consultant',
            'Viewer',
            'Mentor',
            'Employer',
        ];

        $hasParticipantRole = $user->hasAnyRole(
            $participantRoles
        );

        $hasStaffRole = $user->hasAnyRole(
            $staffRoles
        );

        /*
         * Keep staff accounts out of the participant portal.
         */
        if ($hasStaffRole) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'This account belongs to the staff portal. Please use the staff login page.',
                ])
                ->onlyInput('email');
        }

        /*
         * Repair legacy/imported participant accounts.
         *
         * Some older users may have a valid Student/Alumni/Job Seeker
         * role but an incorrect or empty user_type.
         */
        if (
            $user->user_type !== 'participant'
            && $hasParticipantRole
        ) {
            $user->forceFill([
                'user_type' => 'participant',
            ])->save();

            $user->refresh();
        }

        /*
         * If the user is still not recognised as a participant,
         * deny access.
         */
        if ($user->user_type !== 'participant') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'This account is not registered for the participant portal.',
                ])
                ->onlyInput('email');
        }

        /*
         * Prevent inactive or suspended accounts from signing in.
         */
        if (
            in_array(
                $user->status,
                ['inactive', 'suspended'],
                true
            )
        ) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'This account is not currently active.',
                ])
                ->onlyInput('email');
        }

        /*
         * Activate pending participant accounts after successful login,
         * preserving the existing system behaviour.
         */
        $user->forceFill([
            'last_login_at' => now(),
            'status' => $user->status === 'pending'
                ? 'active'
                : $user->status,
        ])->save();

        /*
         * Prevent a stored staff/admin URL from redirecting the participant
         * into the admin area after login.
         */
        $intendedUrl = session()->pull('url.intended');

        if (
            is_string($intendedUrl)
            && $intendedUrl !== ''
            && ! str_contains(
                parse_url(
                    $intendedUrl,
                    PHP_URL_PATH
                ) ?? '',
                '/admin'
            )
        ) {
            return redirect()->to($intendedUrl);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Log out a participant.
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'success',
                'You have been signed out securely.'
            );
    }
}