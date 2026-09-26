<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\CohortModuleRelease;
use App\Models\Course;
use App\Models\CourseCohortLearningSetting;
use App\Models\CourseModule;
use Illuminate\Http\Request;

class ModuleAccessController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $this->authoriseInstructor($course);

        $course->load(['modules','cohorts']);
        $cohortId=(int)($request->get('cohort_id') ?: optional($course->cohorts->first())->id);

        $setting=$cohortId
            ? CourseCohortLearningSetting::firstOrCreate(
                ['course_id'=>$course->id,'cohort_id'=>$cohortId],
                ['sequential_modules'=>true,'instructor_release_required'=>false]
            )
            : null;

        $releases=$cohortId
            ? CohortModuleRelease::where('cohort_id',$cohortId)
                ->whereIn('course_module_id',$course->modules->pluck('id'))
                ->get()->keyBy('course_module_id')
            : collect();

        return view('instructor.module-access',compact('course','cohortId','setting','releases'));
    }

    public function settings(Request $request, Course $course)
    {
        $this->authoriseInstructor($course);

        $data=$request->validate([
            'cohort_id'=>['required','exists:cohorts,id'],
            'sequential_modules'=>['nullable','boolean'],
            'instructor_release_required'=>['nullable','boolean'],
        ]);

        CourseCohortLearningSetting::updateOrCreate(
            ['course_id'=>$course->id,'cohort_id'=>$data['cohort_id']],
            [
                'sequential_modules'=>$request->boolean('sequential_modules'),
                'instructor_release_required'=>$request->boolean('instructor_release_required'),
            ]
        );

        return back()->with('success','Cohort module access settings updated.');
    }

    public function release(Request $request, Course $course, CourseModule $module)
    {
        $this->authoriseInstructor($course);
        abort_unless((int)$module->course_id===(int)$course->id,404);

        $data=$request->validate([
            'cohort_id'=>['required','exists:cohorts,id'],
            'is_released'=>['required','boolean'],
        ]);

        CohortModuleRelease::updateOrCreate(
            ['course_module_id'=>$module->id,'cohort_id'=>$data['cohort_id']],
            [
                'is_released'=>(bool)$data['is_released'],
                'released_at'=>$data['is_released'] ? now() : null,
                'released_by'=>auth()->id(),
            ]
        );

        return back()->with('success',$data['is_released'] ? 'Module released for this cohort.' : 'Module locked for this cohort.');
    }

    private function authoriseInstructor(Course $course): void
    {
        abort_unless(
            $course->instructors()->where('users.id',auth()->id())->exists()
            || auth()->user()->hasPermission('courses.edit'),
            403
        );
    }
}
