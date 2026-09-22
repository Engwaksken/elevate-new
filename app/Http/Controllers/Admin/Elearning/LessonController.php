<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function store(Request $request, CourseModule $module)
    {
        $module->lessons()->create($this->validated($request));
        return back()->with('success','Lesson added.');
    }

    public function update(Request $request, CourseModule $module, Lesson $lesson)
    {
        abort_unless($lesson->course_module_id === $module->id,404);
        $lesson->update($this->validated($request));

        return back()->with('success','Lesson updated.');
    }

    public function destroy(CourseModule $module, Lesson $lesson)
    {
        abort_unless($lesson->course_module_id === $module->id,404);
        $lesson->delete();

        return back()->with('success','Lesson deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'=>['required','string','max:190'],
            'content'=>['nullable','string'],
            'content_type'=>['required','in:text,video,file,link,mixed'],
            'video_url'=>['nullable','url'],
            'external_url'=>['nullable','url'],
            'file_path'=>['nullable','string','max:255'],
            'estimated_minutes'=>['nullable','integer','min:1'],
            'position'=>['nullable','integer','min:1'],
            'is_published'=>['nullable','boolean'],
        ]) + ['is_published'=>$request->boolean('is_published')];
    }
}
