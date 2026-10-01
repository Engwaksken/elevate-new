<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
final class InstructorCourseWorkspaceController extends Controller {
 public function index(Request $request): View {
  $user=$request->user(); abort_unless($user,401); $query=Course::query();
  if(!$this->elevated($user)) $query->whereHas('instructors',fn($q)=>$q->whereKey($user->getKey()));
  if($s=trim((string)$request->query('search'))) $query->where(fn($q)=>$q->where('title','like',"%{$s}%")->orWhere('code','like',"%{$s}%"));
  $courses=$query->latest()->paginate(15)->withQueryString(); return view('admin.my-courses.index',compact('courses'));
 }
 public function show(Request $request,Course $course): View {
  $user=$request->user(); abort_unless($user,401);
  abort_unless($this->elevated($user)||$course->instructors()->whereKey($user->getKey())->exists(),403,'You do not have permission to perform this action.');
  $course->loadMissing(['modules.lessons','assignments','quizzes','participants']); return view('admin.my-courses.show',compact('course'));
 }
 private function elevated($user): bool { return method_exists($user,'isSuperAdmin') && $user->isSuperAdmin(); }
}
