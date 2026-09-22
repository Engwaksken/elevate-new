<?php

namespace App\Http\Controllers\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MentorProfile;
use Illuminate\Http\Request;

class MentorProfileController extends Controller
{
    public function edit()
    {
        return view('mentorship.mentor-profile', [
            'profile'=>MentorProfile::firstOrNew(['user_id'=>auth()->id()]),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'organisation'=>['nullable','string','max:190'],
            'job_title'=>['nullable','string','max:190'],
            'industry'=>['nullable','string','max:190'],
            'years_experience'=>['nullable','integer','min:0','max:80'],
            'professional_bio'=>['nullable','string','max:5000'],
            'skills_text'=>['nullable','string'],
            'languages_text'=>['nullable','string'],
            'mentoring_areas_text'=>['nullable','string'],
            'linkedin_url'=>['nullable','url'],
            'country'=>['nullable','string','max:100'],
        ]);

        MentorProfile::updateOrCreate(
            ['user_id'=>auth()->id()],
            [
                'organisation'=>$data['organisation'] ?? null,
                'job_title'=>$data['job_title'] ?? null,
                'industry'=>$data['industry'] ?? null,
                'years_experience'=>$data['years_experience'] ?? null,
                'professional_bio'=>$data['professional_bio'] ?? null,
                'skills'=>array_values(array_filter(array_map('trim',explode(',',$data['skills_text'] ?? '')))),
                'languages'=>array_values(array_filter(array_map('trim',explode(',',$data['languages_text'] ?? '')))),
                'mentoring_areas'=>array_values(array_filter(array_map('trim',explode(',',$data['mentoring_areas_text'] ?? '')))),
                'linkedin_url'=>$data['linkedin_url'] ?? null,
                'country'=>$data['country'] ?? null,
                'status'=>'pending',
            ]
        );

        return back()->with('success','Mentor profile submitted for approval.');
    }
}
