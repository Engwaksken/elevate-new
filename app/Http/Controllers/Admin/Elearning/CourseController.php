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
        $query=Course::query()
            ->with([
                'branches',
                'modules'=>fn($q)=>$q->with('lessons')->orderBy('position'),
            ])
            ->withCount(['modules','enrolments','assessments']);

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('title','like',"%{$search}%")
                    ->orWhere('code','like',"%{$search}%")
                    ->orWhere('summary','like',"%{$search}%");
            });
        }

        if($status=$request->get('status')){
            $query->where('status',$status);
        }

        if($mode=$request->get('delivery_mode')){
            $query->where('delivery_mode',$mode);
        }

        if ($branchId = $request->integer('branch_id')) {
            $query->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId));
        }

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page')
            : 20;

        return view('admin.elearning.courses.index',[
            'courses'=>$query->latest()->paginate($perPage)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
            'branches'=>Branch::orderBy('name')->get(),
            'stats'=>[
                'total'=>Course::count(),
                'published'=>Course::where('status','published')->count(),
                'draft'=>Course::where('status','draft')->count(),
                'archived'=>Course::where('status','archived')->count(),
            ],
        ]);
    }

    public function create()
    {
        return redirect()
            ->route('admin.elearning.courses.index')
            ->with('open_course_modal','create');
    }

    public function store(Request $request, AuditService $audit)
    {
        $data = $this->validated($request);
        $course = \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $ids = $data['branch_ids'] ?? [];
            unset($data['branch_ids']);
            $course = Course::create($data + ['branch_id' => $ids[0] ?? null, 'created_by' => auth()->id()]);
            $course->branches()->sync($ids);
            return $course;
        });

        $audit->log('courses','created',$course,[],$course->toArray());

        return redirect()
            ->route('admin.elearning.courses.index')
            ->with('success','Course created.');
    }

    public function edit(Course $course)
    {
        return redirect()
            ->route('admin.elearning.courses.index')
            ->with('open_course_modal','edit-'.$course->id);
    }

    public function update(Request $request, Course $course, AuditService $audit)
    {
        $old=$course->toArray();

        $data = $this->validated($request, $course->id);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $course, $request) {
            $sync = $request->has('branch_ids') || $request->boolean('sync_branches') || $request->has('branch_id');
            $ids = $data['branch_ids'] ?? [];
            unset($data['branch_ids']);
            if ($sync) {
                $data['branch_id'] = $ids[0] ?? null;
                $course->branches()->sync($ids);
            }
            $course->update($data);
        });

        $audit->log(
            'courses',
            'updated',
            $course,
            $old,
            $course->fresh()->toArray()
        );

        return redirect()
            ->route('admin.elearning.courses.index')
            ->with('success','Course updated.');
    }

    private function validated(Request $request, ?int $id=null): array
    {
        if (! $request->has('branch_ids') && $request->filled('branch_id')) {
            $request->merge(['branch_ids' => [$request->input('branch_id')]]);
        }
        return $request->validate([
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'branch_ids'=>['nullable','array'],
            'branch_ids.*'=>['integer','distinct','exists:branches,id'],
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
        ])+[
            'self_enrolment_enabled'=>$request->boolean('self_enrolment_enabled'),
        ];
    }
}
