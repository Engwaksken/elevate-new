<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrolment;
use Illuminate\Http\Request;

class CourseCatalogueController extends Controller
{
    public function index(Request $request)
    {
        $query=Course::where('status','published')->select(['id','title','summary','description','thumbnail_path']);

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q
                ->where('title','like',"%{$search}%")
                ->orWhere('summary','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%"));
        }

        if($mode=$request->get('delivery_mode')){
            $query->where('delivery_mode',$mode);
        }

        return view('learning.courses.index',[
            'courses'=>$query->latest()->paginate(12)->withQueryString(),
        ]);
    }

    public function show(Course $course)
    {
        abort_unless($course->status==='published',404);
        $participant = auth()->check() && auth()->user()->isParticipant() && auth()->user()->isActive();
        $enrolled = $participant
            && Enrolment::where('course_id', $course->id)->where('user_id', auth()->id())->exists();

        $course->loadMissing(['entryAssessment', 'entrySurvey']);
        $entryAssessment = $course->entryAssessment;
        $entrySurvey = $course->entrySurvey;
        $entryAssessmentPending = ! $enrolled && $participant && $course->entryRequirementPendingFor(auth()->id());

        return view('learning.courses.show', compact('course', 'enrolled', 'entryAssessment', 'entrySurvey', 'entryAssessmentPending'));
    }
}
