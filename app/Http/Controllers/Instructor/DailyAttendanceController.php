<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\Enrolment;
use Illuminate\Http\Request;

class DailyAttendanceController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $this->authoriseInstructor($course);

        $dateFrom=$request->date('from') ?: now()->startOfMonth();
        $dateTo=$request->date('to') ?: now()->endOfMonth();

        $sessions=AttendanceSession::query()
            ->where('course_id',$course->id)
            ->whereBetween('session_date',[$dateFrom->toDateString(),$dateTo->toDateString()])
            ->orderBy('session_date')
            ->get();

        $records=AttendanceRecord::with('user')
            ->whereIn('attendance_session_id',$sessions->pluck('id'))
            ->get();

        $enrolments=Enrolment::with('user')
            ->where('course_id',$course->id)
            ->get();

        $matrix=[];
        foreach($enrolments as $enrolment){
            $matrix[$enrolment->user_id]=[
                'user'=>$enrolment->user,
                'days'=>[],
                'present'=>0,'absent'=>0,'late'=>0,'excused'=>0,
            ];
        }

        foreach($records as $record){
            if(!isset($matrix[$record->user_id])) continue;
            $session=$sessions->firstWhere('id',$record->attendance_session_id);
            if(!$session) continue;

            $day=$session->session_date->format('Y-m-d');
            $matrix[$record->user_id]['days'][$day]=$record->status;
            if(isset($matrix[$record->user_id][$record->status])){
                $matrix[$record->user_id][$record->status]++;
            }
        }

        return view('instructor.attendance-daily',[
            'course'=>$course,
            'sessions'=>$sessions,
            'matrix'=>$matrix,
            'from'=>$dateFrom,
            'to'=>$dateTo,
        ]);
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
