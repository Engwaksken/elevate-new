<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Http\Request;

class CourseModuleController extends Controller
{
    public function store(Request $request, Course $course)
    {
        $data = $request->validate([
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
            'position'=>['nullable','integer','min:1'],
            'is_published'=>['nullable','boolean'],
        ]);

        $course->modules()->create($data + ['is_published'=>$request->boolean('is_published')]);

        return back()->with('success','Module added.');
    }

    public function update(Request $request, Course $course, CourseModule $module)
    {
        abort_unless($module->course_id === $course->id,404);

        $module->update($request->validate([
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
            'position'=>['nullable','integer','min:1'],
            'is_published'=>['nullable','boolean'],
        ]) + ['is_published'=>$request->boolean('is_published')]);

        return back()->with('success','Module updated.');
    }

    public function destroy(Course $course, CourseModule $module)
    {
        abort_unless($module->course_id === $course->id,404);
        $module->delete();

        return back()->with('success','Module deleted.');
    }
}
