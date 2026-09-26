<?php
namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningFile;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LearningFileAdminController extends Controller
{
    public function index(Request $request)
    {
        $query=LearningFile::query()->latest();

        if($courseId=$request->get('course_id')) $query->where('course_id',$courseId);

        if($search=trim((string)$request->get('search'))){
            $query->where('original_name','like',"%{$search}%");
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

    public function store(Request $request,Course $course,?Lesson $lesson=null)
    {
        $request->validate([
            'file'=>['required','file','max:51200','mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,mp4,mp3,zip'],
        ]);

        $upload=$request->file('file');
        $stored=Str::uuid().'.'.$upload->getClientOriginalExtension();
        $path=$upload->storeAs("learning/{$course->id}",$stored,'local');

        LearningFile::create([
            'course_id'=>$course->id,
            'lesson_id'=>$lesson?->id,
            'original_name'=>$upload->getClientOriginalName(),
            'stored_name'=>$stored,
            'disk'=>'local',
            'path'=>$path,
            'mime_type'=>$upload->getMimeType(),
            'size_bytes'=>$upload->getSize(),
            'uploaded_by'=>auth()->id(),
        ]);

        return back()->with('success','Learning file uploaded securely.');
    }

    public function destroy(LearningFile $file)
    {
        if($file->path && Storage::disk($file->disk ?: 'local')->exists($file->path)){
            Storage::disk($file->disk ?: 'local')->delete($file->path);
        }

        $file->delete();

        return back()->with('success','Learning file deleted.');
    }
}
