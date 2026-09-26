<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Cohort,Course,Event,EventAttendanceRecord};
use Illuminate\Http\Request;
class EventController extends Controller{
 public function index(Request $r){
  $q=Event::withCount(['registrations','attendanceRecords'])->with(['cohort','course']);
  if($s=trim((string)$r->search))$q->where(fn($x)=>$x->where('title','like',"%$s%")->orWhere('venue','like',"%$s%")->orWhere('district','like',"%$s%"));
  if($r->event_type)$q->where('event_type',$r->event_type);
  if($r->filled('from'))$q->whereDate('starts_at','>=',$r->date('from'));
  if($r->filled('to'))$q->whereDate('starts_at','<=',$r->date('to'));
  return view('admin.events.index',['events'=>$q->latest('starts_at')->paginate(20)->withQueryString(),'cohorts'=>Cohort::orderBy('name')->get(),'courses'=>Course::orderBy('title')->get(),'stats'=>['total'=>Event::count(),'upcoming'=>Event::where('starts_at','>=',now())->count(),'published'=>Event::where('is_published',true)->count(),'attendance'=>EventAttendanceRecord::whereIn('attendance_status',['present','late'])->count()]]);
 }
 public function store(Request $r){Event::create($this->v($r)+['registration_required'=>$r->boolean('registration_required'),'is_published'=>$r->boolean('is_published'),'created_by'=>auth()->id()]);return back()->with('success','Event created.');}
 public function update(Request $r,Event $event){$event->update($this->v($r)+['registration_required'=>$r->boolean('registration_required'),'is_published'=>$r->boolean('is_published')]);return back()->with('success','Event updated.');}
 public function destroy(Event $event){$event->delete();return back()->with('success','Event archived.');}
 public function attendance(Event $event){$event->load(['registrations.user','attendanceRecords']);return view('admin.events.attendance',compact('event'));}
 public function saveAttendance(Request $r,Event $event){$d=$r->validate(['attendance'=>['required','array'],'attendance.*.status'=>['required','in:present,late,absent,excused'],'attendance.*.notes'=>['nullable','string','max:2000']]);foreach($d['attendance'] as $id=>$row){$reg=$event->registrations()->find($id);if(!$reg)continue;EventAttendanceRecord::updateOrCreate(['event_id'=>$event->id,'event_registration_id'=>$reg->id],['user_id'=>$reg->user_id,'attendance_status'=>$row['status'],'check_in_at'=>in_array($row['status'],['present','late'],true)?now():null,'notes'=>$row['notes']??null,'recorded_by'=>auth()->id()]);}return back()->with('success','Event attendance updated.');}
 private function v(Request $r):array{return $r->validate(['title'=>['required','string','max:190'],'event_type'=>['required','in:training,workshop,webinar,meeting,mentorship,career_fair,community,other'],'description'=>['nullable','string'],'starts_at'=>['required','date'],'ends_at'=>['nullable','date','after_or_equal:starts_at'],'venue'=>['nullable','string','max:190'],'district'=>['nullable','string','max:100'],'delivery_mode'=>['required','in:physical,online,hybrid'],'meeting_url'=>['nullable','url','max:1000'],'capacity'=>['nullable','integer','min:1'],'cohort_id'=>['nullable','exists:cohorts,id'],'course_id'=>['nullable','exists:courses,id']]);}
}