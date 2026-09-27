@extends('layouts.admin')
@section('title',$course->title.' | Instructor Workspace')
@section('content')
<style>
.icm-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.icm-stat{background:#fff;border:1px solid #e5e7eb;border-left:4px solid #800000;border-radius:12px;padding:16px}.icm-stat small{display:block;color:#667085;font-weight:700}.icm-stat strong{font-size:1.35rem}.icm-tabs{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}.icm-tab-nav{display:flex;gap:4px;overflow-x:auto;padding:10px;border-bottom:1px solid #e5e7eb;background:#fafafa}.icm-tab-btn{border:0;background:transparent;padding:10px 14px;border-radius:8px;font-weight:700;color:#475467;white-space:nowrap;cursor:pointer;text-decoration:none}.icm-tab-btn.active{background:#800000;color:#fff}.icm-pane{display:none;padding:18px}.icm-pane.active{display:block}.icm-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.icm-card{border:1px solid #e5e7eb;border-radius:12px;padding:16px}.icm-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.icm-form .full{grid-column:1/-1}.icm-form input,.icm-form textarea,.icm-form select{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d0d5dd;border-radius:9px;background:#fff}.icm-form textarea{min-height:90px}.icm-list{display:grid;gap:10px}.icm-row{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;padding:12px;border:1px solid #e5e7eb;border-radius:10px}.icm-muted{color:#667085;font-size:.88rem}@media(max-width:900px){.icm-stats,.icm-grid,.icm-form{grid-template-columns:1fr}.icm-form .full{grid-column:auto}}
</style>

<div class="admin-page-header">
<div><span class="admin-eyebrow">Instructor / Trainer Workspace</span><h1>{{ $course->title }}</h1><p>Manage content and participants for this assigned course only.</p></div>
<a href="{{ route('instructor.dashboard') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> My Courses</a>
</div>

<div class="icm-stats">
<div class="icm-stat"><small>Modules</small><strong>{{ $stats['modules'] }}</strong></div>
<div class="icm-stat"><small>Lessons</small><strong>{{ $stats['lessons'] }}</strong></div>
<div class="icm-stat"><small>Assignments / Quizzes</small><strong>{{ $stats['assessments'] }}</strong></div>
<div class="icm-stat"><small>Participants</small><strong>{{ $stats['participants'] }}</strong></div>
</div>

<div class="icm-tabs">
<div class="icm-tab-nav">
<button class="icm-tab-btn active" data-tab="modules"><i class="fas fa-layer-group"></i> Modules</button>
<button class="icm-tab-btn" data-tab="lessons"><i class="fas fa-book-open"></i> Lessons</button>
<button class="icm-tab-btn" data-tab="assessments"><i class="fas fa-list-check"></i> Assignments & Quizzes</button>
<button class="icm-tab-btn" data-tab="participants"><i class="fas fa-users"></i> Participants</button>
@if(Route::has('instructor.module-access.index'))<a class="icm-tab-btn" href="{{ route('instructor.module-access.index',$course) }}"><i class="fas fa-lock-open"></i> Module Access</a>@endif
@if(Route::has('instructor.attendance.create'))<a class="icm-tab-btn" href="{{ route('instructor.attendance.create',$course) }}"><i class="fas fa-user-check"></i> Attendance</a>@endif
</div>

<section class="icm-pane active" data-pane="modules">
<div class="icm-grid">
<div class="icm-card">
<h3>Add Module</h3>
<form method="POST" action="{{ route('instructor.courses.modules.store',$course) }}" class="icm-form">@csrf
<div><label>Module title</label><input name="title" placeholder="Enter module title" required></div>
<div><label>Position</label><input type="number" min="1" name="position" placeholder="e.g. 1"></div>
<div class="full"><label>Description</label><textarea name="description" placeholder="Describe this module"></textarea></div>
<label class="full"><input type="checkbox" name="is_published" value="1"> Publish module</label>
<div class="full"><button class="btn btn-primary"><i class="fas fa-plus"></i> Add Module</button></div>
</form>
</div>
<div class="icm-card"><h3>Current Modules</h3><div class="icm-list">
@forelse($course->modules as $module)
<div class="icm-row"><div><strong>{{ $module->position }}. {{ $module->title }}</strong><div class="icm-muted">{{ $module->lessons->count() }} lesson(s) · {{ $module->is_published?'Published':'Draft' }}</div></div>
<form method="POST" action="{{ route('instructor.courses.modules.destroy',[$course,$module]) }}">@csrf @method('DELETE')<button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i></button></form></div>
@empty<div class="icm-muted">No modules added yet.</div>@endforelse
</div></div></div>
</section>

<section class="icm-pane" data-pane="lessons">
<div class="icm-card"><h3>Add Lesson</h3>
@if($course->modules->isEmpty())<p class="icm-muted">Create a module first.</p>@else
<form method="POST" action="{{ route('instructor.courses.lessons.store',[$course,$course->modules->first()]) }}" class="icm-form" id="lessonForm">@csrf
<div><label>Module</label><select id="lessonModule">@foreach($course->modules as $module)<option value="{{ route('instructor.courses.lessons.store',[$course,$module]) }}">{{ $module->title }}</option>@endforeach</select></div>
<div><label>Lesson title</label><input name="title" placeholder="Enter lesson title" required></div>
<div><label>Content type</label><select name="content_type"><option value="text">Text</option><option value="video">Video</option><option value="link">Link</option><option value="mixed">Mixed</option><option value="file">File</option></select></div>
<div><label>Estimated minutes</label><input type="number" min="1" name="estimated_minutes" placeholder="30"></div>
<div class="full"><label>Content</label><textarea name="content" placeholder="Enter lesson content"></textarea></div>
<div><label>Video URL</label><input type="url" name="video_url" placeholder="https://..."></div>
<div><label>External URL</label><input type="url" name="external_url" placeholder="https://..."></div>
<label class="full"><input type="checkbox" name="is_published" value="1"> Publish lesson</label>
<div class="full"><button class="btn btn-primary"><i class="fas fa-plus"></i> Add Lesson</button></div>
</form>@endif
</div>
<div class="icm-card" style="margin-top:16px"><h3>Lessons</h3><div class="icm-list">
@foreach($course->modules as $module)<div><strong>{{ $module->title }}</strong>
@forelse($module->lessons as $lesson)<div class="icm-row" style="margin-top:8px"><div><strong>{{ $lesson->title }}</strong><div class="icm-muted">{{ ucfirst($lesson->content_type) }} · {{ $lesson->is_published?'Published':'Draft' }}</div></div><form method="POST" action="{{ route('instructor.courses.lessons.destroy',[$course,$module,$lesson]) }}">@csrf @method('DELETE')<button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i></button></form></div>@empty<div class="icm-muted">No lessons.</div>@endforelse
</div>@endforeach
</div></div>
</section>

<section class="icm-pane" data-pane="assessments">
<div class="icm-grid"><div class="icm-card"><h3>Add Assignment / Quiz</h3>
<form method="POST" action="{{ route('instructor.courses.assessments.store',$course) }}" class="icm-form">@csrf
<div><label>Title</label><input name="title" placeholder="Assessment title" required></div>
<div><label>Type</label><select name="type"><option value="assignment">Assignment</option><option value="quiz">Quiz</option><option value="exam">Exam</option></select></div>
<div><label>Pass mark</label><input type="number" min="0" max="100" name="pass_mark" value="50" required></div>
<div><label>Max attempts</label><input type="number" min="1" max="20" name="max_attempts" value="1" required></div>
<div class="full"><label>Instructions</label><textarea name="instructions" placeholder="Instructions for participants"></textarea></div>
<label class="full"><input type="checkbox" name="is_published" value="1"> Publish</label>
<div class="full"><button class="btn btn-primary"><i class="fas fa-plus"></i> Add Assessment</button></div>
</form></div>
<div class="icm-card"><h3>Assessments</h3><div class="icm-list">@forelse($course->assessments as $assessment)<div class="icm-row"><div><strong>{{ $assessment->title }}</strong><div class="icm-muted">{{ ucfirst($assessment->type) }} · {{ $assessment->questions->count() }} question(s)</div></div></div>@empty<div class="icm-muted">No assessments yet.</div>@endforelse</div></div></div>
@foreach($course->assessments as $assessment)
<div class="icm-card" style="margin-top:16px"><h3>Add Question: {{ $assessment->title }}</h3>
<form method="POST" action="{{ route('instructor.courses.assessments.questions.store',[$course,$assessment]) }}" class="icm-form">@csrf
<div><label>Question type</label><select name="question_type"><option value="multiple_choice">Multiple choice</option><option value="true_false">True / False</option><option value="short_text">Short text</option><option value="long_text">Long text</option></select></div>
<div><label>Marks</label><input type="number" step="0.1" min="0.1" name="marks" value="1"></div>
<div class="full"><label>Question</label><textarea name="question_text" placeholder="Enter question" required></textarea></div>
<div class="full"><label>Options</label><textarea name="options_text" placeholder="One option per line"></textarea></div>
<div><label>Correct value</label><input name="correct_value" placeholder="Correct option/value"></div>
<div class="full"><button class="btn btn-primary">Add Question</button></div>
</form></div>
@endforeach
</section>

<section class="icm-pane" data-pane="participants">
<div class="icm-card"><form method="GET" class="icm-form" style="margin-bottom:14px"><div><label>Search participant</label><input name="participant_search" value="{{ request('participant_search') }}" placeholder="Name or email"></div><div style="align-self:end"><button class="btn btn-outline">Search</button></div></form>
<div class="icm-list">@forelse($participants as $enrolment)
<div class="icm-row"><div><strong>{{ $enrolment->user?->name ?? 'Participant' }}</strong><div class="icm-muted">{{ $enrolment->user?->email }} · {{ number_format((float)($enrolment->progress_percent??0),0) }}% progress</div></div>
<form method="POST" action="{{ route('instructor.courses.participants.update',[$course,$enrolment]) }}" style="display:flex;gap:6px;flex-wrap:wrap">@csrf @method('PUT')
<select name="status">@foreach(['enrolled','active','in_progress','completed','withdrawn','cancelled'] as $status)<option value="{{ $status }}" @selected($enrolment->status===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select>
<input style="width:90px" type="number" min="0" max="100" step="0.1" name="progress_percent" value="{{ $enrolment->progress_percent }}" placeholder="%">
<button class="btn btn-primary btn-sm">Save</button></form></div>
@empty<div class="icm-muted">No participants enrolled.</div>@endforelse</div>{{ $participants->links() }}</div>
</section>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
 document.querySelectorAll('.icm-tab-btn[data-tab]').forEach(btn=>btn.addEventListener('click',function(){
  document.querySelectorAll('.icm-tab-btn[data-tab]').forEach(x=>x.classList.remove('active'));
  document.querySelectorAll('.icm-pane').forEach(x=>x.classList.remove('active'));
  btn.classList.add('active');
  document.querySelector('[data-pane="'+btn.dataset.tab+'"]')?.classList.add('active');
 }));
 const m=document.getElementById('lessonModule'),f=document.getElementById('lessonForm');
 if(m&&f){m.addEventListener('change',()=>f.action=m.value);f.action=m.value;}
});
</script>
@endsection
