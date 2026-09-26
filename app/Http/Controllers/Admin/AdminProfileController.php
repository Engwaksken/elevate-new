<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile.edit',[
            'user'=>auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user=auth()->user();

        $data=$request->validate([
            'name'=>['required','string','max:190'],
            'email'=>[
                'required','email','max:190',
                Rule::unique('users','email')->ignore($user->id),
            ],
        ]);

        $user->update($data);

        return back()->with('success','Profile updated.');
    }

    public function password(Request $request)
    {
        $user=auth()->user();

        $data=$request->validate([
            'current_password'=>['required','string'],
            'password'=>['required','string','min:8','confirmed'],
        ]);

        if(!Hash::check($data['current_password'],$user->password)){
            throw ValidationException::withMessages([
                'current_password'=>'The current password is incorrect.',
            ]);
        }

        $user->update([
            'password'=>Hash::make($data['password']),
        ]);

        return back()->with('success','Password changed successfully.');
    }
}
