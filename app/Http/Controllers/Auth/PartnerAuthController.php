<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PartnerAuthController extends Controller
{
    private const TYPES = ['employer', 'mentor'];

    public function showLogin(string $type)
    {
        $this->ensureType($type);

        return view('auth.partner-login', ['type' => $type]);
    }

    public function login(Request $request, string $type)
    {
        $this->ensureType($type);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->user_type !== $type && ! $user->hasRole($type)) {
            Auth::logout();
            $request->session()->invalidate();

            throw ValidationException::withMessages([
                'email' => 'This account is not registered as an '.$type.'. Use the correct sign-in page.',
            ]);
        }

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();

            throw ValidationException::withMessages([
                'email' => $user->status === 'pending'
                    ? 'Your account is still awaiting administrator approval.'
                    : 'Your account is not active. Please contact an administrator.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route($this->home($type)));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function home(string $type): string
    {
        return $type === 'mentor' ? 'mentorship.dashboard' : 'employer.jobs.index';
    }

    private function ensureType(string $type): void
    {
        abort_unless(in_array($type, self::TYPES, true), 404);
    }
}
