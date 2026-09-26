<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit',[
            'user'=>$request->user()->load('profile'),
            'branches'=>Branch::query()
                ->where('is_active',true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user=$request->user();

        $data=$request->validate([
            'name'=>['required','string','max:255'],
            'email'=>[
                'required',
                'email',
                'max:255',
                Rule::unique('users','email')->ignore($user->id),
            ],
            'phone'=>['nullable','string','max:30'],
            'branch_id'=>['nullable','exists:branches,id'],
            'surname'=>['nullable','string','max:255'],
            'given_name'=>['nullable','string','max:255'],
            'other_name'=>['nullable','string','max:255'],
            'gender'=>['nullable',Rule::in(['female','male','other','prefer_not_to_say'])],
            'date_of_birth'=>['nullable','date','before_or_equal:today'],
            'country'=>['nullable','string','max:255'],
            'district'=>['nullable','string','max:255'],
            'location'=>['nullable','string','max:255'],
            'is_pwd'=>['nullable','boolean'],
            'education_level'=>['nullable','string','max:255'],
            'employment_status'=>['nullable','string','max:255'],
            'career_interests'=>['nullable','string','max:5000'],
            'preferred_language'=>['nullable',Rule::in(['en','lg','sw'])],
            'current_password'=>['nullable','string'],
            'password'=>[
                'nullable',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ]);

        if(!empty($data['password'])){
            if(
                empty($data['current_password'])
                || !Hash::check($data['current_password'],$user->password)
            ){
                throw ValidationException::withMessages([
                    'current_password'=>'Enter your current password before setting a new password.',
                ]);
            }
        }

        DB::transaction(function() use($user,$data){
            $user->fill([
                'name'=>$data['name'],
                'email'=>strtolower($data['email']),
                'phone'=>$data['phone'] ?? null,
            ]);

            if(!empty($data['password'])){
                $user->password=$data['password'];
            }

            $user->save();

            $user->profile()->updateOrCreate(
                ['user_id'=>$user->id],
                [
                    'branch_id'=>$data['branch_id'] ?? null,
                    'surname'=>$data['surname'] ?? null,
                    'given_name'=>$data['given_name'] ?? null,
                    'other_name'=>$data['other_name'] ?? null,
                    'gender'=>$data['gender'] ?? null,
                    'date_of_birth'=>$data['date_of_birth'] ?? null,
                    'country'=>$data['country'] ?? null,
                    'district'=>$data['district'] ?? null,
                    'location'=>$data['location'] ?? null,
                    'is_pwd'=>(bool)($data['is_pwd'] ?? false),
                    'education_level'=>$data['education_level'] ?? null,
                    'employment_status'=>$data['employment_status'] ?? null,
                    'career_interests'=>$data['career_interests'] ?? null,
                    'preferred_language'=>$data['preferred_language'] ?? 'en',
                ]
            );
        });

        return back()->with('success','Profile updated successfully.');
    }
}
