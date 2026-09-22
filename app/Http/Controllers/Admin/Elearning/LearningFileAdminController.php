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
    public function store(Request $request, Course $course, ?Lesson $lesson = null)
    {
        $request->validate([
            'file'=>['required','file','max:51200','mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,mp4,mp3,zip'],
        ]);

        $upload = $request->file('file');
        $stored = Str::uuid().'.'.$upload->getClientOriginalExtension();
        $path = $upload->storeAs("learning/{$course->id}",$stored,'local');

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
}
