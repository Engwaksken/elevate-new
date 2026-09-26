<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.passwords.email');
    }

    public function email(Request $request)
    {
        $request->validate([
            'email'=>['required','email'],
        ]);

        $status=Password::sendResetLink(
            ['email'=>strtolower($request->string('email')->toString())]
        );

        return $status===Password::RESET_LINK_SENT
            ? back()->with('success',__($status))
            : back()->withErrors(['email'=>__($status)])->onlyInput('email');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.passwords.reset',[
            'token'=>$token,
            'email'=>$request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'=>['required'],
            'email'=>['required','email'],
            'password'=>[
                'required',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers(),
            ],
        ]);

        $status=Password::reset(
            [
                'email'=>strtolower($request->string('email')->toString()),
                'password'=>$request->input('password'),
                'password_confirmation'=>$request->input('password_confirmation'),
                'token'=>$request->input('token'),
            ],
            function(User $user,string $password){
                $user->forceFill([
                    'password'=>Hash::make($password),
                    'remember_token'=>Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status===Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success',__($status))
            : back()->withErrors(['email'=>__($status)])->withInput($request->only('email'));
    }
}
