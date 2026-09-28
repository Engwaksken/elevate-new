<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->isStaff() && $user->isActive()) {
                return redirect()->route('admin.dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return view('auth.staff-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'email' => strtolower($credentials['email']),
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The supplied staff credentials are incorrect.',
            ]);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if (! $user->isStaff() || ! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account is not authorised for the WITU staff portal.',
            ]);
        }

        try {
            $user->forceFill(['last_login_at' => now()])->save();
        } catch (\Throwable $e) {
            report($e);
        }

        // Never allow a stale intended URL to send staff/admin to /instructor/dashboard.
        $request->session()->forget('url.intended');

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('success', 'You have been signed out securely.');
    }
}
