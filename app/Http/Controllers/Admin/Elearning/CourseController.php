<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Programme;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::query();

        if ($search = trim((string)$request->get('search'))) {
            $query->where(fn($q) => $q->where('title','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%"));
        }

        if ($status = $request->get('status')) {
            $query->where('status',$status);
        }

        return view('admin.elearning.courses.index', [
            'courses'=>$query->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function create()
    {
        return $this->form(new Course());
    }

    public function store(Request $request, AuditService $audit)
    {
        $course = Course::create($this->validated($request) + ['created_by'=>auth()->id()]);
        $audit->log('courses','created',$course,[],$course->toArray());

        return redirect()->route('admin.elearning.courses.index')->with('success','Course created.');
    }

    public function edit(Course $course)
    {
        return $this->form($course);
    }

    public function update(Request $request, Course $course, AuditService $audit)
    {
        $old = $course->toArray();
        $course->update($this->validated($request,$course->id));
        $audit->log('courses','updated',$course,$old,$course->fresh()->toArray());

        return redirect()->route('admin.elearning.courses.index')->with('success','Course updated.');
    }

    private function form(Course $course)
    {
        return view('admin.elearning.courses.form', [
            'course'=>$course,
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
            'branches'=>Branch::where('is_active',true)->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request, ?int $id=null): array
    {
        return $request->validate([
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'branch_id'=>['nullable','exists:branches,id'],
            'title'=>['required','string','max:190'],
            'code'=>['nullable','string','max:50','unique:courses,code,'.($id ?? 'NULL')],
            'summary'=>['nullable','string','max:1000'],
            'description'=>['nullable','string'],
            'delivery_mode'=>['required','in:online,in_person,blended'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'duration_hours'=>['nullable','integer','min:1'],
            'pass_mark'=>['required','numeric','min:0','max:100'],
            'self_enrolment_enabled'=>['nullable','boolean'],
            'status'=>['required','in:draft,published,archived'],
        ]) + ['self_enrolment_enabled'=>$request->boolean('self_enrolment_enabled')];
    }
}
