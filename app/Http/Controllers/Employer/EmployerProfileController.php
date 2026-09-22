<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use Illuminate\Http\Request;

class EmployerProfileController extends Controller
{
    public function edit()
    {
        return view('employer.profile', [
            'employer'=>Employer::firstOrNew(['owner_user_id'=>auth()->id()]),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name'=>['required','string','max:190'],
            'company_type'=>['nullable','string','max:100'],
            'industry'=>['nullable','string','max:150'],
            'website'=>['nullable','url'],
            'contact_person'=>['nullable','string','max:190'],
            'email'=>['nullable','email'],
            'phone'=>['nullable','string','max:30'],
            'country'=>['nullable','string','max:100'],
            'location'=>['nullable','string','max:190'],
            'description'=>['nullable','string'],
        ]);

        Employer::updateOrCreate(
            ['owner_user_id'=>auth()->id()],
            $data + ['status'=>'pending']
        );

        return back()->with('success','Employer profile submitted for approval.');
    }
}
