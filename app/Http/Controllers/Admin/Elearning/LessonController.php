<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Services\Learning\LearningFileService;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function store(Request $request, CourseModule $module)
    {
        $lesson=$module->lessons()->create($this->validated($request));
        $this->storeFiles($request,$module,$lesson);

        return back()->with('success','Lesson added.');
    }

    public function update(Request $request, CourseModule $module, Lesson $lesson)
    {
        abort_unless($lesson->course_module_id === $module->id,404);
        $lesson->update($this->validated($request));

        $ids=collect((array)$request->input('remove_files',[]))->map(fn($id)=>(int)$id)->filter();
        if($ids->isNotEmpty()){
            $lesson->files()->whereIn('id',$ids)->get()->each(fn($file)=>$this->files()->deleteLearningFile($file));
        }

        $this->storeFiles($request,$module,$lesson);

        return back()->with('success','Lesson updated.');
    }

    public function destroy(CourseModule $module, Lesson $lesson)
    {
        abort_unless($lesson->course_module_id === $module->id,404);
        $this->files()->deleteAllFor($lesson);
        $lesson->delete();

        return back()->with('success','Lesson deleted.');
    }

    private function files(): LearningFileService
    {
        return app(LearningFileService::class);
    }

    private function storeFiles(Request $request, CourseModule $module, Lesson $lesson): void
    {
        $this->files()->storeMaterials(
            $this->files()->uploadedFiles($request,'resource_files'),
            (int)$module->course_id,
            $lesson,
            null,
            $request->user()
        );
    }

    private function validated(Request $request): array
    {
        $request->validate($this->files()->uploadRules('resource_files','lesson_mimes') + [
            'remove_files'=>['nullable','array'],
            'remove_files.*'=>['integer'],
        ]);

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
