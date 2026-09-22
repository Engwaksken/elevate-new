<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Http\Request;

class ResumeSectionController extends Controller
{
    public function addExperience(Request $request, Resume $resume)
    {
        $this->authorise($resume);
        $resume->experiences()->create($request->validate([
            'job_title'=>['required','string','max:190'],
            'organisation'=>['required','string','max:190'],
            'location'=>['nullable','string','max:190'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'is_current'=>['nullable','boolean'],
            'description'=>['nullable','string'],
        ]) + ['is_current'=>$request->boolean('is_current')]);

        return back()->with('success','Experience added.');
    }

    public function addEducation(Request $request, Resume $resume)
    {
        $this->authorise($resume);
        $resume->education()->create($request->validate([
            'institution'=>['required','string','max:190'],
            'qualification'=>['required','string','max:190'],
            'field_of_study'=>['nullable','string','max:190'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'description'=>['nullable','string'],
        ]));

        return back()->with('success','Education added.');
    }

    public function addSkill(Request $request, Resume $resume)
    {
        $this->authorise($resume);
        $resume->skills()->create($request->validate([
            'skill'=>['required','string','max:100'],
            'level'=>['nullable','string','max:50'],
        ]));

        return back()->with('success','Skill added.');
    }

    private function authorise(Resume $resume): void
    {
        abort_unless($resume->user_id === auth()->id(),403);
    }
}
