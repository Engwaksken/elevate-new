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
    public function create(): View
    {
        return view('auth.register', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(ParticipantRegisterRequest $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['given_name'].' '.$data['surname']),
                'email' => strtolower($data['email']),
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
                'is_pwd' => (bool)($data['is_pwd'] ?? false),
                'education_level' => $data['education_level'] ?? null,
                'employment_status' => $data['employment_status'] ?? null,
                'career_interests' => $data['career_interests'] ?? null,
                'preferred_language' => $data['preferred_language'] ?? 'English',
            ]);

            if ($role = Role::where('slug', 'student')->first()) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            foreach (['privacy_policy', 'terms'] as $type) {
                Consent::create([
                    'user_id' => $user->id,
                    'consent_type' => $type,
                    'policy_version' => config('app.policy_version', '1.0'),
                    'accepted' => true,
                    'accepted_at' => now(),
                    'ip_address' => request()->ip(),
                ]);
            }

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $audit->log('auth', 'participant_registered', $user);

        return redirect()->route('verification.notice')
            ->with('success', 'Account created. Please verify your email address.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->user_type !== 'participant') {
            Auth::logout();
            return back()->withErrors(['email' => 'Please use the staff login page.']);
        }

        if (in_array($user->status, ['inactive', 'suspended'], true)) {
            Auth::logout();
            return back()->withErrors(['email' => 'This account is not currently active.']);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'status' => $user->status === 'pending' ? 'active' : $user->status,
        ])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
