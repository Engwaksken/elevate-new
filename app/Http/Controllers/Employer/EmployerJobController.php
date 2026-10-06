<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\Job;
use Illuminate\Http\Request;

class EmployerJobController extends Controller
{
    use ExportsTables;

    public function index(Request $request)
    {
        $employer = Employer::where('owner_user_id',auth()->id())->first();

        if(!$employer){
            return redirect()->route('employer.profile.edit')
                ->with('info','Set up your company profile before posting jobs.');
        }

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'My Job Postings',$employer->jobs()->withCount('applications')->latest(),[
                'Title'=>'title',
                'Industry'=>'industry',
                'Location'=>fn($j)=>collect([$j->location,$j->country])->filter()->implode(', '),
                'Positions'=>'positions',
                'Deadline'=>'application_deadline',
                'Applications'=>'applications_count',
                'Status'=>fn($j)=>ucfirst((string)$j->status),
                'Posted'=>'created_at',
            ], null, [], fn($e)=>$e->subtitle($employer->company_name));
        }

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
