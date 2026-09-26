<?php

namespace App\Http\Controllers\Admin\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JobAdminController extends Controller
{
    public function index(Request $request)
    {
        $query=Job::with('employer')->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q->where('title','like',"%{$search}%")
                ->orWhere('industry','like',"%{$search}%")
                ->orWhere('location','like',"%{$search}%")
                ->orWhereHas('employer',fn($e)=>$e->where('company_name','like',"%{$search}%")));
        }

        if($status=$request->get('status')) $query->where('status',$status);
        if($type=$request->get('employment_type')) $query->where('employment_type',$type);

        $routePrefix=str_starts_with((string)$request->route()?->getName(),'admin.hr.jobs')
            ? 'admin.hr.jobs'
            : 'admin.jobs';

        return view('admin.jobs.index',[
            'jobs'=>$query->paginate(25)->withQueryString(),
            'employers'=>Employer::where('status','approved')->orderBy('company_name')->get(),
            'routePrefix'=>$routePrefix,
            'stats'=>[
                'total'=>Job::count(),
                'published'=>Job::where('status','published')->count(),
                'draft'=>Job::whereIn('status',['draft','pending'])->count(),
                'closed'=>Job::whereIn('status',['closed','rejected'])->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Job::create($this->validated($request)+[
            'skills'=>$this->skills($request->input('skills_text')),
            'published_at'=>$request->input('status')==='published' ? now() : null,
        ]);

        return back()->with('success','Job created.');
    }

    public function update(Request $request, Job $job)
    {
        $data=$this->validated($request,$job);
        $data['skills']=$this->skills($request->input('skills_text'));

        if($data['status']==='published' && !$job->published_at){
            $data['published_at']=now();
        }

        $job->update($data);

        return back()->with('success','Job updated.');
    }

    public function destroy(Job $job)
    {
        $job->delete();
        return back()->with('success','Job archived.');
    }

    public function import(Request $request)
    {
        $data=$request->validate([
            'employer_id'=>['required','exists:employers,id'],
            'file'=>['required','file','mimes:xlsx,xls,csv,txt','max:10240'],
        ]);

        $workbook=IOFactory::load($request->file('file')->getRealPath());
        $rows=$workbook->getSheet(0)->toArray(null,true,true,false);
        if(!$rows) return back()->with('error','No job rows were found in the uploaded file.');

        $headers=array_map(fn($h)=>Str::of((string)$h)->trim()->lower()->replace([' ','-','.'],'_')->toString(),array_shift($rows));
        $created=0;$skipped=0;

        foreach($rows as $row){
            if(count($row)!==count($headers)){ $skipped++; continue; }
            $record=array_combine($headers,$row);
            $title=trim((string)($record['title'] ?? $record['job_title'] ?? ''));
            if($title===''){ $skipped++; continue; }

            Job::create([
                'employer_id'=>$data['employer_id'],
                'title'=>$title,
                'category'=>$record['category'] ?? null,
                'industry'=>$record['industry'] ?? null,
                'location'=>$record['location'] ?? null,
                'country'=>$record['country'] ?? 'Uganda',
                'employment_type'=>$record['employment_type'] ?? 'full_time',
                'work_arrangement'=>$record['work_arrangement'] ?? 'onsite',
                'experience_level'=>$record['experience_level'] ?? null,
                'education_level'=>$record['education_level'] ?? null,
                'salary_min'=>is_numeric($record['salary_min'] ?? null) ? $record['salary_min'] : null,
                'salary_max'=>is_numeric($record['salary_max'] ?? null) ? $record['salary_max'] : null,
                'salary_currency'=>$record['salary_currency'] ?? 'UGX',
                'description'=>$record['description'] ?? null,
                'responsibilities'=>$record['responsibilities'] ?? null,
                'requirements'=>$record['requirements'] ?? null,
                'skills'=>$this->skills($record['skills'] ?? ''),
                'application_deadline'=>$record['application_deadline'] ?? null,
                'positions'=>is_numeric($record['positions'] ?? null) ? (int)$record['positions'] : 1,
                'status'=>$record['status'] ?? 'draft',
                'published_at'=>($record['status'] ?? '')==='published' ? now() : null,
            ]);
            $created++;
        }

        return back()->with('success',"{$created} job(s) imported. {$skipped} row(s) skipped.");
    }

    public function publish(Job $job){ $job->update(['status'=>'published','published_at'=>now()]); return back()->with('success','Job published.'); }
    public function reject(Job $job){ $job->update(['status'=>'rejected']); return back()->with('success','Job rejected.'); }

    private function validated(Request $request,?Job $job=null): array
    {
        return $request->validate([
            'employer_id'=>['required','exists:employers,id'],
            'title'=>['required','string','max:190'],
            'category'=>['nullable','string','max:100'],
            'industry'=>['nullable','string','max:100'],
            'location'=>['nullable','string','max:190'],
            'country'=>['required','string','max:100'],
            'employment_type'=>['required','string','max:50'],
            'work_arrangement'=>['required','string','max:50'],
            'experience_level'=>['nullable','string','max:100'],
            'education_level'=>['nullable','string','max:190'],
            'salary_min'=>['nullable','numeric','min:0'],
            'salary_max'=>['nullable','numeric','gte:salary_min'],
            'salary_currency'=>['required','string','size:3'],
            'description'=>['required','string'],
            'responsibilities'=>['nullable','string'],
            'requirements'=>['nullable','string'],
            'application_deadline'=>['nullable','date'],
            'positions'=>['required','integer','min:1'],
            'status'=>['required','in:draft,pending,published,closed,rejected'],
        ]);
    }

    private function skills(?string $value): array
    {
        return array_values(array_filter(array_map('trim',explode(',',(string)$value))));
    }
}
