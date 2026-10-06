<?php
namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Admin\Concerns\BulkDeletesRecords;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningFile;
use App\Models\Lesson;
use App\Services\Learning\LearningFileService;
use Illuminate\Http\Request;

class LearningFileAdminController extends Controller
{
    use ExportsTables;
    use BulkDeletesRecords;

    protected function bulkDeleteModel(): string
    {
        return LearningFile::class;
    }

    public function index(Request $request)
    {
        $query=LearningFile::query()->with(['lesson:id,title','assessment:id,title,type'])->latest();

        if($courseId=$request->get('course_id')) $query->where('course_id',$courseId);

        if($search=trim((string)$request->get('search'))){
            $query->where('original_name','like',"%{$search}%");
        }

        if($format=$this->exportFormat($request)){
            $courseTitles=Course::query()->pluck('title','id');
            return $this->exportTable($format,'Learning Files',$query,[
                'File'=>'original_name',
                'Course'=>fn($f)=>$courseTitles[$f->course_id]??'',
                'Attached to'=>fn($f)=>self::attachedTo($f),
                'Participant access'=>fn($f)=>app(LearningFileService::class)->isDownloadable($f)?'Downloadable':'View only',
                'Type'=>'mime_type',
                'Size (KB)'=>fn($f)=>$f->size_bytes!==null?round($f->size_bytes/1024,1):'',
                'Uploaded'=>'created_at',
            ],null,['course_id'=>'Course']);
        }

        return view('admin.elearning.files.index',[
            'files'=>$query->paginate(25)->withQueryString(),
            'courses'=>Course::orderBy('title')->get(),
            'stats'=>[
                'total'=>LearningFile::count(),
                'pdf'=>LearningFile::where('mime_type','like','%pdf%')->count(),
                'media'=>LearningFile::where(function($q){
                    $q->where('mime_type','like','video/%')->orWhere('mime_type','like','audio/%');
                })->count(),
                'size'=>LearningFile::sum('size_bytes'),
            ],
        ]);
    }

    /** "Lesson: X" / "Assignment: Y" / "Course" label for a file. */
    public static function attachedTo(LearningFile $file): string
    {
        if($file->lesson_id) return 'Lesson: '.($file->lesson?->title ?? '#'.$file->lesson_id);
        if($file->assessment_id) return ucfirst($file->assessment?->type ?? 'assessment').': '.($file->assessment?->title ?? '#'.$file->assessment_id);

        return 'Course';
    }

    /** Upload one ("file") or several ("files[]") course/lesson files. */
    public function store(Request $request,Course $course,?Lesson $lesson=null)
    {
        $files=app(LearningFileService::class);

        $request->validate($files->uploadRules('files','lesson_mimes','file'));
        $uploads=$files->uploadedFiles($request,'file','files');

        if($uploads===[]){
            return back()->withErrors(['files'=>'Choose at least one file to upload.']);
        }

        $stored=$files->storeMaterials($uploads,$course->id,$lesson,null,$request->user());

        return back()->with('success',$stored->count().' learning file(s) uploaded securely.');
    }

    public function destroy(LearningFile $file)
    {
        app(LearningFileService::class)->deleteLearningFile($file);

        return back()->with('success','Learning file deleted.');
    }
}
