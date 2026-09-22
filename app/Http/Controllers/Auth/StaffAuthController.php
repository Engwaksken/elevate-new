<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.staff-login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if ($user->user_type !== 'staff') {
            Auth::logout();
            return back()->withErrors(['email' => 'This login is for WITU staff only.']);
        }

        if ($user->status !== 'active') {
            Auth::logout();
            return back()->withErrors(['email' => 'Your staff account is not active.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
