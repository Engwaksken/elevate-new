<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Admin\Concerns\BulkDeletesRecords;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Programme;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    use ExportsTables;
    use BulkDeletesRecords;

    protected function bulkDeleteModel(): string
    {
        return Course::class;
    }

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

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Courses',(clone $query)->setEagerLoads(['branches'=>fn($q)=>$q])->latest(),[
                'Course'=>'title',
                'Code'=>'code',
                'Branches'=>fn($c)=>$c->branches->pluck('name')->join(', '),
                'Mode'=>'delivery_mode',
                'Start date'=>'start_date',
                'End date'=>'end_date',
                'Modules'=>'modules_count',
                'Enrolments'=>'enrolments_count',
                'Assessments'=>'assessments_count',
                'Status'=>'status',
            ],null,['branch_id'=>'Branch','delivery_mode'=>'Delivery mode']);
        }

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page')
            : 20;

        return view('admin.elearning.courses.index',[
            'courses'=>$query->latest()->paginate($perPage)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
            'branches'=>Branch::orderBy('name')->get(),
            'assessments'=>Assessment::orderBy('title')->get(['id','title','course_id']),
            'surveys'=>\App\Models\Survey::orderBy('title')->get(['id','title']),
            'courseOptions'=>Course::orderBy('title')->get(['id','title']),
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
        $data = $this->withImage($request, $this->validated($request));
        try {
            $course = \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
                $ids = $data['branch_ids'] ?? [];
                $compulsoryIds = $data['compulsory_course_ids'] ?? [];
                unset($data['branch_ids'], $data['compulsory_course_ids']);
                $course = Course::create($data + ['branch_id' => $ids[0] ?? null, 'created_by' => auth()->id()]);
                $course->branches()->sync($ids);
                $course->compulsoryCourses()->sync($this->withoutSelf($compulsoryIds, $course->id));
                return $course;
            });
        } catch (\Throwable $exception) {
            if ($request->hasFile('thumbnail')) Storage::disk('public')->delete($data['thumbnail_path']);
            throw $exception;
        }

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

        $oldImage = $course->thumbnail_path;
        $data = $this->withImage($request, $this->validated($request, $course->id));
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($data, $course, $request) {
                $sync = $request->has('branch_ids') || $request->boolean('sync_branches') || $request->has('branch_id');
                $ids = $data['branch_ids'] ?? [];
                $compulsoryIds = $data['compulsory_course_ids'] ?? [];
                $syncCompulsory = $request->has('compulsory_course_ids') || $request->boolean('sync_compulsory');
                unset($data['branch_ids'], $data['compulsory_course_ids']);
                if ($sync) {
                    $data['branch_id'] = $ids[0] ?? null;
                    $course->branches()->sync($ids);
                }
                if ($syncCompulsory) {
                    $course->compulsoryCourses()->sync($this->withoutSelf($compulsoryIds, $course->id));
                }
                $course->update($data);
            });
        } catch (\Throwable $exception) {
            if ($request->hasFile('thumbnail')) Storage::disk('public')->delete($data['thumbnail_path']);
            throw $exception;
        }
        if ($oldImage && $oldImage !== $course->thumbnail_path && str_starts_with($oldImage, 'course-thumbnails/')) {
            Storage::disk('public')->delete($oldImage);
        }

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
            'thumbnail'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'remove_thumbnail'=>['nullable','boolean'],
            'delivery_mode'=>['required','in:online,in_person,blended'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'duration_hours'=>['nullable','integer','min:1'],
            'pass_mark'=>['required','numeric','min:0','max:100'],
            'self_enrolment_enabled'=>['nullable','boolean'],
            'entry_assessment_id'=>['nullable','exists:assessments,id'],
            'entry_survey_id'=>['nullable','exists:surveys,id'],
            'compulsory_course_ids'=>['nullable','array'],
            'compulsory_course_ids.*'=>['integer','distinct','exists:courses,id'],
            'status'=>['required','in:draft,published,archived'],
        ])+[
            'self_enrolment_enabled'=>$request->boolean('self_enrolment_enabled'),
        ];
    }

    private function withoutSelf(array $ids, int $courseId): array
    {
        return array_values(array_filter(
            array_map('intval', $ids),
            fn (int $id) => $id !== $courseId
        ));
    }

    private function withImage(Request $request, array $data): array
    {
        unset($data['thumbnail'], $data['remove_thumbnail']);
        if ($request->hasFile('thumbnail')) $data['thumbnail_path'] = $request->file('thumbnail')->store('course-thumbnails', 'public');
        elseif ($request->boolean('remove_thumbnail')) $data['thumbnail_path'] = null;
        return $data;
    }
}
