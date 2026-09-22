<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\Job;
use Illuminate\Http\Request;

class EmployerJobController extends Controller
{
    public function index()
    {
        $employer = Employer::where('owner_user_id',auth()->id())->firstOrFail();

        return view('employer.jobs', [
            'employer'=>$employer,
            'jobs'=>$employer->jobs()->latest()->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $employer = Employer::where('owner_user_id',auth()->id())
            ->where('status','approved')
            ->firstOrFail();

        $data = $request->validate([
            'title'=>['required','string','max:190'],
            'category'=>['nullable','string','max:100'],
            'industry'=>['nullable','string','max:150'],
            'location'=>['nullable','string','max:190'],
            'country'=>['nullable','string','max:100'],
            'employment_type'=>['nullable','in:full_time,part_time,contract,internship,temporary,volunteer'],
            'work_arrangement'=>['nullable','in:onsite,remote,hybrid'],
            'experience_level'=>['nullable','string','max:100'],
            'education_level'=>['nullable','string','max:150'],
            'salary_min'=>['nullable','numeric','min:0'],
            'salary_max'=>['nullable','numeric','gte:salary_min'],
            'salary_currency'=>['nullable','string','size:3'],
            'description'=>['nullable','string'],
            'responsibilities'=>['nullable','string'],
            'requirements'=>['nullable','string'],
            'skills_text'=>['nullable','string'],
            'application_deadline'=>['nullable','date','after_or_equal:today'],
            'positions'=>['required','integer','min:1'],
        ]);

        $employer->jobs()->create([
            ...$data,
            'skills'=>array_values(array_filter(array_map('trim',explode(',',$data['skills_text'] ?? '')))),
            'status'=>'pending_approval',
        ]);

        return back()->with('success','Job submitted for approval.');
    }
}
