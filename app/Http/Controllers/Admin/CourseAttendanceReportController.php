<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Cohort;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CourseAttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $sessions=AttendanceSession::query();

        if($request->filled('course_id')){
            $sessions->where('course_id',$request->integer('course_id'));
        }

        if($request->filled('cohort_id')){
            $sessions->where('cohort_id',$request->integer('cohort_id'));
        }

        if($request->filled('from')){
            $sessions->whereDate('session_date','>=',$request->date('from'));
        }

        if($request->filled('to')){
            $sessions->whereDate('session_date','<=',$request->date('to'));
        }

        $sessionIds=(clone $sessions)->pluck('id');

        $query=AttendanceRecord::query()
            ->whereIn('attendance_session_id',$sessionIds)
            ->with(['session','user']);

        if($status=$request->get('status')){
            $query->where('status',$status);
        }

        if($search=trim((string)$request->get('search'))){
            $query->whereHas('user',fn($q)=>$q->where('name','like',"%{$search}%")
                ->orWhere('email','like',"%{$search}%"));
        }

        $all=(clone $query)->get();
        $present=$all->whereIn('status',['present','late'])->count();

        return view('admin.attendance.course-report',[
            'records'=>$query->latest('id')->paginate(30)->withQueryString(),
            'courses'=>Course::orderBy('title')->get(),
            'cohorts'=>Cohort::orderBy('name')->get(),
            'stats'=>[
                'sessions'=>(clone $sessions)->count(),
                'records'=>$all->count(),
                'present'=>$present,
                'rate'=>$all->count()>0 ? round(($present/$all->count())*100,1) : 0,
            ],
        ]);
    }

    public function csv(Request $request)
    {
        $sessions=AttendanceSession::query();

        if($request->filled('course_id')){
            $sessions->where('course_id',$request->integer('course_id'));
        }

        if($request->filled('cohort_id')){
            $sessions->where('cohort_id',$request->integer('cohort_id'));
        }

        if($request->filled('from')){
            $sessions->whereDate('session_date','>=',$request->date('from'));
        }

        if($request->filled('to')){
            $sessions->whereDate('session_date','<=',$request->date('to'));
        }

        $sessionIds=$sessions->pluck('id');

        $query=AttendanceRecord::whereIn('attendance_session_id',$sessionIds)
            ->with(['session','user']);

        if($status=$request->get('status')){
            $query->where('status',$status);
        }

        if($search=trim((string)$request->get('search'))){
            $query->whereHas('user',fn($q)=>$q->where('name','like',"%{$search}%")
                ->orWhere('email','like',"%{$search}%"));
        }

        $records=$query->orderBy('attendance_session_id')->orderBy('user_id')->get();

        return response()->streamDownload(function() use($records){
            $out=fopen('php://output','w');
            fputcsv($out,['Date','Session','Course ID','Cohort ID','Participant','Email','Status','Remarks']);

            foreach($records as $record){
                fputcsv($out,[
                    optional($record->session?->session_date)->format('Y-m-d'),
                    $record->session?->title,
                    $record->session?->course_id,
                    $record->session?->cohort_id,
                    $record->user?->name,
                    $record->user?->email,
                    $record->status,
                    $record->remarks,
                ]);
            }

            fclose($out);
        },'course-attendance-report-'.now()->format('Ymd_His').'.csv',[
            'Content-Type'=>'text/csv',
        ]);
    }

    public function participant(Request $request)
    {
        $query=DB::table('attendance_records as ar')
            ->join('users as u','u.id','=','ar.user_id')
            ->selectRaw("
                u.id,
                u.name,
                u.email,
                COUNT(ar.id) as attendance_total,
                SUM(CASE WHEN ar.status IN ('present','late') THEN 1 ELSE 0 END) as attendance_present,
                SUM(CASE WHEN ar.status='absent' THEN 1 ELSE 0 END) as attendance_absent,
                SUM(CASE WHEN ar.status='late' THEN 1 ELSE 0 END) as attendance_late
            ")
            ->groupBy('u.id','u.name','u.email');

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('u.name','like',"%{$search}%")
                    ->orWhere('u.email','like',"%{$search}%");
            });
        }

        return view('admin.attendance.participant-summary',[
            'participants'=>$query->orderBy('u.name')->paginate(30)->withQueryString(),
        ]);
    }
}