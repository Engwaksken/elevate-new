<?php

namespace App\Services\Learning;

use App\Models\CohortModuleRelease;
use App\Models\CourseCohortLearningSetting;
use App\Models\Enrolment;
use App\Models\LessonProgress;
use App\Models\CourseModule;
use App\Models\User;

class ModuleAccessService
{
    public function canAccess(CourseModule $module, User $user): bool
    {
        $module->loadMissing('course');

        $enrolment=Enrolment::query()
            ->where('course_id',$module->course_id)
            ->where('user_id',$user->id)
            ->first();

        if(!$enrolment) return false;
        if(!$enrolment->cohort_id) return true;

        $setting=CourseCohortLearningSetting::query()
            ->where('course_id',$module->course_id)
            ->where('cohort_id',$enrolment->cohort_id)
            ->first();

        if(!$setting || !$setting->sequential_modules){
            return true;
        }

        $modules=CourseModule::query()
            ->where('course_id',$module->course_id)
            ->where('is_published',true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $index=$modules->search(fn($candidate)=>(int)$candidate->id===(int)$module->id);

        if($index===false) return false;

        if($setting->instructor_release_required){
            $released=CohortModuleRelease::query()
                ->where('course_module_id',$module->id)
                ->where('cohort_id',$enrolment->cohort_id)
                ->where('is_released',true)
                ->exists();

            if(!$released && $index!==0){
                return false;
            }
        }

        if($index===0) return true;

        $previous=$modules[$index-1];
        return $this->moduleComplete($previous,$user);
    }

    public function moduleComplete(CourseModule $module, User $user): bool
    {
        $lessonIds=$module->lessons()
            ->where('is_published',true)
            ->pluck('id');

        if($lessonIds->isEmpty()) return true;

        $completed=LessonProgress::query()
            ->where('user_id',$user->id)
            ->whereIn('lesson_id',$lessonIds)
            ->whereNotNull('completed_at')
            ->distinct('lesson_id')
            ->count('lesson_id');

        return $completed===$lessonIds->count();
    }

    public function stateForCourse(int $courseId,int $cohortId): array
    {
        $setting=CourseCohortLearningSetting::firstOrCreate(
            ['course_id'=>$courseId,'cohort_id'=>$cohortId],
            ['sequential_modules'=>true,'instructor_release_required'=>false]
        );

        return [
            'setting'=>$setting,
            'releases'=>CohortModuleRelease::query()
                ->where('cohort_id',$cohortId)
                ->whereHas('module',fn($q)=>$q->where('course_id',$courseId))
                ->get()
                ->keyBy('course_module_id'),
        ];
    }
}
