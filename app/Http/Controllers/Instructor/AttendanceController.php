<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\Enrolment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function create(Course $course)
    {
        $this->authoriseInstructor($course);

        $learners=Enrolment::with('user')
            ->where('course_id',$course->id)
            ->whereHas('user',fn($q)=>$q->where('user_type','participant'))
            ->orderBy('user_id')
            ->get();

        $recentSessions=AttendanceSession::withCount('records')
            ->where('course_id',$course->id)
            ->latest('session_date')
            ->limit(8)
            ->get();

        return view('instructor.attendance-create',[
            'course'=>$course->load('cohorts'),
            'learners'=>$learners,
            'recentSessions'=>$recentSessions,
            'stats'=>[
                'learners'=>$learners->count(),
                'sessions'=>AttendanceSession::where('course_id',$course->id)->count(),
                'present'=>AttendanceRecord::whereHas(
                    'session',
                    fn($q)=>$q->where('course_id',$course->id)
                )->where('status','present')->count(),
                'absent'=>AttendanceRecord::whereHas(
                    'session',
                    fn($q)=>$q->where('course_id',$course->id)
                )->where('status','absent')->count(),
            ],
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $this->authoriseInstructor($course);

        $data=$request->validate([
            'title'=>['required','string','max:190'],
            'cohort_id'=>[
                'nullable',
                'exists:cohorts,id',
            ],
            'session_date'=>['required','date'],
            'starts_at'=>['nullable','date_format:H:i'],
            'ends_at'=>['nullable','date_format:H:i','after:starts_at'],
            'venue'=>['nullable','string','max:190'],
            'attendance'=>['required','array','min:1'],
            'attendance.*'=>[
                'required',
                Rule::in(['present','absent','late','excused']),
            ],
        ]);

        $validUserIds=Enrolment::query()
            ->where('course_id',$course->id)
            ->whereIn('user_id',array_keys($data['attendance']))
            ->pluck('user_id')
            ->map(fn($id)=>(int)$id)
            ->all();

        if(count($validUserIds)!==count($data['attendance'])){
            abort(422,'Attendance can only be recorded for learners enrolled in this course.');
        }

        DB::transaction(function() use($course,$data,$validUserIds){
            $session=AttendanceSession::create([
                'course_id'=>$course->id,
                'cohort_id'=>$data['cohort_id'] ?? null,
                'title'=>$data['title'],
                'session_date'=>$data['session_date'],
                'starts_at'=>$data['starts_at'] ?? null,
                'ends_at'=>$data['ends_at'] ?? null,
                'venue'=>$data['venue'] ?? null,
            ]);

            foreach($validUserIds as $userId){
                AttendanceRecord::create([
                    'attendance_session_id'=>$session->id,
                    'user_id'=>$userId,
                    'status'=>$data['attendance'][$userId],
                ]);
            }
        });

        return back()->with('success','Attendance recorded successfully.');
    }

    private function authoriseInstructor(Course $course): void
    {
        abort_unless(
            $course->instructors()
                ->where('users.id',auth()->id())
                ->exists()
            || auth()->user()->hasPermission('courses.edit'),
            403
        );
    }
}
