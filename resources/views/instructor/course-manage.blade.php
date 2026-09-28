@extends('layouts.admin')
@section('title',$course->title.' | Instructor Workspace')

@section('content')
<style>
.icm-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}
.icm-stat{background:#fff;border:1px solid #e5e7eb;border-left:4px solid #800000;border-radius:12px;padding:16px}
.icm-stat small{display:block;color:#667085;font-weight:700}.icm-stat strong{font-size:1.35rem}
.icm-tabs{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}
.icm-tab-nav{display:flex;gap:4px;overflow-x:auto;padding:10px;border-bottom:1px solid #e5e7eb;background:#fafafa}
.icm-tab-btn{border:0;background:transparent;padding:10px 14px;border-radius:8px;font-weight:700;color:#475467;white-space:nowrap;cursor:pointer;text-decoration:none}
.icm-tab-btn.active{background:#800000;color:#fff}
.icm-pane{display:none;padding:18px}.icm-pane.active{display:block}
.icm-toolbar{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
.icm-filter{display:grid;grid-template-columns:2fr repeat(3,minmax(150px,1fr)) auto;gap:10px;margin-bottom:16px}
.icm-filter input,.icm-filter select,.icm-form input,.icm-form textarea,.icm-form select{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d0d5dd;border-radius:9px;background:#fff}
.icm-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.icm-lessons-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.icm-card{border:1px solid #e5e7eb;border-radius:12px;padding:16px;background:#fff}
.icm-card h3{margin-top:0}.icm-muted{color:#667085;font-size:.88rem}
.icm-row{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;padding:12px;border:1px solid #e5e7eb;border-radius:10px}
.icm-list{display:grid;gap:10px}
.icm-actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:12px}
.icm-chip{display:inline-flex;padding:4px 8px;border-radius:999px;background:#f2f4f7;font-size:.75rem;font-weight:700;color:#344054}
.icm-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.icm-form .full{grid-column:1/-1}.icm-form textarea{min-height:90px}
.icm-modal{position:fixed;inset:0;background:rgba(16,24,40,.55);display:none;align-items:center;justify-content:center;padding:20px;z-index:9999}
.icm-modal.open{display:flex}.icm-modal-dialog{width:min(760px,96vw);max-height:90vh;overflow:auto;background:#fff;border-radius:16px;box-shadow:0 24px 80px rgba(0,0,0,.25)}
.icm-modal-head{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:16px 18px;border-bottom:1px solid #e5e7eb;position:sticky;top:0;background:#fff;z-index:2}
.icm-modal-body{padding:18px}.icm-modal-close{border:0;background:#f2f4f7;border-radius:8px;width:36px;height:36px;cursor:pointer}
.icm-pagination{margin-top:16px}
@media(max-width:1200px){.icm-lessons-grid{grid-template-columns:repeat(2,1fr)}.icm-filter{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.icm-stats,.icm-grid-2,.icm-form,.icm-filter{grid-template-columns:1fr}.icm-form .full{grid-column:auto}}
@media(max-width:620px){.icm-lessons-grid{grid-template-columns:1fr}}
</style>

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Instructor / Trainer Workspace</span>
        <h1>{{ $course->title }}</h1>
        <p>Manage authorised content, assessments and participants for this assigned course.</p>
    </div>
    <a href="{{ route('admin.my-courses') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> My Courses</a>
</div>

<div class="icm-stats">
    <div class="icm-stat"><small>Modules</small><strong>{{ number_format($stats['modules']) }}</strong></div>
    <div class="icm-stat"><small>Lessons</small><strong>{{ number_format($stats['lessons']) }}</strong></div>
    <div class="icm-stat"><small>Assignments / Quizzes / Exams</small><strong>{{ number_format($stats['assessments']) }}</strong></div>
    <div class="icm-stat"><small>Participants</small><strong>{{ number_format($stats['participants']) }}</strong></div>
</div>

<div class="icm-tabs">
    <div class="icm-tab-nav">
        <button class="icm-tab-btn active" data-tab="modules"><i class="fas fa-layer-group"></i> Modules</button>
        <button class="icm-tab-btn" data-tab="lessons"><i class="fas fa-book-open"></i> Lessons</button>
        <button class="icm-tab-btn" data-tab="assessments"><i class="fas fa-list-check"></i> Assignments, Quizzes & Exams</button>
        <button class="icm-tab-btn" data-tab="participants"><i class="fas fa-users"></i> Participants</button>
        @if(Route::has('instructor.module-access.index'))<a class="icm-tab-btn" href="{{ route('instructor.module-access.index',$course) }}"><i class="fas fa-lock-open"></i> Module Access</a>@endif
        @if(Route::has('instructor.attendance.create'))<a class="icm-tab-btn" href="{{ route('instructor.attendance.create',$course) }}"><i class="fas fa-user-check"></i> Attendance</a>@endif
    </div>

    <section class="icm-pane active" data-pane="modules">
        <div class="icm-toolbar">
            <div><h2 style="margin:0">Modules</h2><div class="icm-muted">Create and organise course modules.</div></div>
            <button type="button" class="btn btn-primary" data-open-modal="moduleModal"><i class="fas fa-plus"></i> Add Module</button>
        </div>

        <form method="GET" class="icm-filter">
            <input type="hidden" name="tab" value="modules">
            <div><label>Search</label><input name="module_search" value="{{ request('module_search') }}" placeholder="Module title or description"></div>
            <div><label>Period</label><select name="period"><option value="">All periods</option>@foreach(['today'=>'Today','week'=>'This week','month'=>'This month','quarter'=>'This quarter','year'=>'This year'] as $v=>$l)<option value="{{ $v }}" @selected(request('period')===$v)>{{ $l }}</option>@endforeach</select></div>
            <div></div><div></div>
            <button class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
        </form>

        <div class="icm-grid-2">
            @forelse($modules as $module)
                <div class="icm-card">
                    <div style="display:flex;justify-content:space-between;gap:10px">
                        <div>
                            <span class="icm-chip">Module {{ $module->position }}</span>
                            <h3 style="margin:8px 0">{{ $module->title }}</h3>
                            <div class="icm-muted">{{ $module->lessons_count }} lesson(s) · {{ $module->is_published ? 'Published' : 'Draft' }}</div>
                        </div>
                        <form method="POST" action="{{ route('instructor.courses.modules.destroy',[$course,$module]) }}" onsubmit="return confirm('Delete this module and its related content?')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i></button></form>
                    </div>
                    @if($module->description)<p>{{ \Illuminate\Support\Str::limit($module->description,180) }}</p>@endif
                </div>
            @empty
                <div class="icm-card">No modules found.</div>
            @endforelse
        </div>
        <div class="icm-pagination">{{ $modules->appends(['tab'=>'modules'])->links() }}</div>
    </section>

    <section class="icm-pane" data-pane="lessons">
        <div class="icm-toolbar">
            <div><h2 style="margin:0">Lessons</h2><div class="icm-muted">Lessons display four per row on desktop.</div></div>
            <button type="button" class="btn btn-primary" data-open-modal="lessonModal" @disabled($course->modules()->count()===0)><i class="fas fa-plus"></i> Add Lesson</button>
        </div>

        <form method="GET" class="icm-filter">
            <input type="hidden" name="tab" value="lessons">
            <div><label>Search</label><input name="lesson_search" value="{{ request('lesson_search') }}" placeholder="Lesson title or content"></div>
            <div><label>Period</label><select name="period"><option value="">All periods</option>@foreach(['today'=>'Today','week'=>'This week','month'=>'This month','quarter'=>'This quarter','year'=>'This year'] as $v=>$l)<option value="{{ $v }}" @selected(request('period')===$v)>{{ $l }}</option>@endforeach</select></div>
            <div><label>Module</label><select name="module_id"><option value="">All modules</option>@foreach($course->modules()->orderBy('position')->get() as $m)<option value="{{ $m->id }}" @selected((string)request('module_id')===(string)$m->id)>{{ $m->title }}</option>@endforeach</select></div>
            <div><label>Type</label><select name="lesson_type"><option value="">All types</option>@foreach(['text','video','file','link','mixed'] as $type)<option value="{{ $type }}" @selected(request('lesson_type')===$type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
            <button class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
        </form>

        <div class="icm-lessons-grid">
            @forelse($lessons as $lesson)
                <article class="icm-card">
                    <span class="icm-chip">{{ ucfirst($lesson->content_type) }}</span>
                    <h3 style="margin:8px 0">{{ $lesson->title }}</h3>
                    <div class="icm-muted">{{ $lesson->module?->title }} · {{ $lesson->estimated_minutes ?: '—' }} min</div>
                    @if($lesson->content)<p>{{ \Illuminate\Support\Str::limit(strip_tags($lesson->content),120) }}</p>@endif
                    <div class="icm-actions">
                        @if($lesson->video_url)<a class="btn btn-outline btn-sm" target="_blank" href="{{ $lesson->video_url }}"><i class="fas fa-video"></i> Video</a>@endif
                        @if($lesson->external_url)<a class="btn btn-outline btn-sm" target="_blank" href="{{ $lesson->external_url }}"><i class="fas fa-link"></i> Link</a>@endif
                        @if($lesson->file_path && Route::has('instructor.courses.lessons.file'))
                            <a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.lessons.file',[$course,$lesson->module,$lesson]) }}"><i class="fas fa-download"></i> File</a>
                        @endif
                        <form method="POST" action="{{ route('instructor.courses.lessons.destroy',[$course,$lesson->module,$lesson]) }}" onsubmit="return confirm('Delete this lesson?')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i></button></form>
                    </div>
                </article>
            @empty
                <div class="icm-card" style="grid-column:1/-1">No lessons found.</div>
            @endforelse
        </div>
        <div class="icm-pagination">{{ $lessons->appends(['tab'=>'lessons'])->links() }}</div>
    </section>

    <section class="icm-pane" data-pane="assessments">
        <div class="icm-toolbar">
            <div><h2 style="margin:0">Assignments, Quizzes & Exams</h2><div class="icm-muted">Create assessments, attach files and add questions.</div></div>
            <button type="button" class="btn btn-primary" data-open-modal="assessmentModal"><i class="fas fa-plus"></i> Add Assessment</button>
        </div>

        <form method="GET" class="icm-filter">
            <input type="hidden" name="tab" value="assessments">
            <div><label>Search</label><input name="assessment_search" value="{{ request('assessment_search') }}" placeholder="Title or instructions"></div>
            <div><label>Period</label><select name="assessment_period"><option value="">All periods</option>@foreach(['today'=>'Today','week'=>'This week','month'=>'This month','quarter'=>'This quarter','year'=>'This year'] as $v=>$l)<option value="{{ $v }}" @selected(request('assessment_period')===$v)>{{ $l }}</option>@endforeach</select></div>
            <div><label>Type</label><select name="assessment_type"><option value="">All types</option>@foreach(['assignment','quiz','exam'] as $type)<option value="{{ $type }}" @selected(request('assessment_type')===$type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
            <div></div>
            <button class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
        </form>

        <div class="icm-grid-2">
            @forelse($assessments as $assessment)
                <article class="icm-card">
                    <div style="display:flex;justify-content:space-between;gap:10px">
                        <div>
                            <span class="icm-chip">{{ ucfirst($assessment->type) }}</span>
                            <h3 style="margin:8px 0">{{ $assessment->title }}</h3>
                            <div class="icm-muted">{{ $assessment->questions_count }} question(s) · Pass {{ number_format((float)$assessment->pass_mark,0) }}%</div>
                        </div>
                    </div>
                    @if($assessment->due_at)<p><strong>Due:</strong> {{ $assessment->due_at->format('d M Y, g:i A') }}</p>@endif
                    <div class="icm-actions">
                        <button type="button" class="btn btn-primary btn-sm" data-open-question="{{ $assessment->id }}" data-assessment-title="{{ $assessment->title }}"><i class="fas fa-circle-question"></i> Add Question</button>
                        @if($assessment->attachment_path && Route::has('instructor.courses.assessments.file'))
                            <a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.assessments.file',[$course,$assessment]) }}"><i class="fas fa-paperclip"></i> Download File</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="icm-card" style="grid-column:1/-1">No assessments found.</div>
            @endforelse
        </div>
        <div class="icm-pagination">{{ $assessments->appends(['tab'=>'assessments'])->links() }}</div>
    </section>

    <section class="icm-pane" data-pane="participants">
        <div class="icm-toolbar"><div><h2 style="margin:0">Participants</h2><div class="icm-muted">Search, filter and track participant progress.</div></div></div>

        <form method="GET" class="icm-filter">
            <input type="hidden" name="tab" value="participants">
            <div><label>Search</label><input name="participant_search" value="{{ request('participant_search') }}" placeholder="Name or email"></div>
            <div><label>Period</label><select name="participant_period"><option value="">All periods</option>@foreach(['today'=>'Today','week'=>'This week','month'=>'This month','quarter'=>'This quarter','year'=>'This year'] as $v=>$l)<option value="{{ $v }}" @selected(request('participant_period')===$v)>{{ $l }}</option>@endforeach</select></div>
            <div><label>Cohort</label><select name="cohort_id"><option value="">All cohorts</option>@foreach($course->cohorts as $cohort)<option value="{{ $cohort->id }}" @selected((string)request('cohort_id')===(string)$cohort->id)>{{ $cohort->name }}</option>@endforeach</select></div>
            <div><label>Status</label><select name="participant_status"><option value="">All statuses</option>@foreach(['enrolled','active','in_progress','completed','withdrawn','cancelled'] as $status)<option value="{{ $status }}" @selected(request('participant_status')===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
            <button class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
        </form>

        <div class="icm-list">
            @forelse($participants as $enrolment)
                <div class="icm-row">
                    <div><strong>{{ $enrolment->user?->name ?? 'Participant' }}</strong><div class="icm-muted">{{ $enrolment->user?->email }} · {{ $enrolment->cohort?->name ?? 'No cohort' }} · {{ number_format((float)($enrolment->progress_percent??0),0) }}% progress</div></div>
                    <form method="POST" action="{{ route('instructor.courses.participants.update',[$course,$enrolment]) }}" style="display:flex;gap:6px;flex-wrap:wrap">@csrf @method('PUT')
                        <select name="status">@foreach(['enrolled','active','in_progress','completed','withdrawn','cancelled'] as $status)<option value="{{ $status }}" @selected($enrolment->status===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select>
                        <input style="width:90px" type="number" min="0" max="100" step="0.1" name="progress_percent" value="{{ $enrolment->progress_percent }}" placeholder="%">
                        <button class="btn btn-primary btn-sm">Save</button>
                    </form>
                </div>
            @empty
                <div class="icm-card">No participants found.</div>
            @endforelse
        </div>
        <div class="icm-pagination">{{ $participants->appends(['tab'=>'participants'])->links() }}</div>
    </section>
</div>

<div class="icm-modal" id="moduleModal" aria-hidden="true">
    <div class="icm-modal-dialog">
        <div class="icm-modal-head"><h3>Add Module</h3><button type="button" class="icm-modal-close" data-close-modal>&times;</button></div>
        <div class="icm-modal-body">
            <form method="POST" action="{{ route('instructor.courses.modules.store',$course) }}" class="icm-form">@csrf
                <div><label>Module title</label><input name="title" placeholder="Enter module title" required></div>
                <div><label>Position</label><input type="number" min="1" name="position" placeholder="e.g. 1"></div>
                <div class="full"><label>Description</label><textarea name="description" placeholder="Describe this module"></textarea></div>
                <label class="full"><input type="checkbox" name="is_published" value="1"> Publish module</label>
                <div class="full"><button class="btn btn-primary"><i class="fas fa-save"></i> Save Module</button></div>
            </form>
        </div>
    </div>
</div>

<div class="icm-modal" id="lessonModal" aria-hidden="true">
    <div class="icm-modal-dialog">
        <div class="icm-modal-head"><h3>Add Lesson</h3><button type="button" class="icm-modal-close" data-close-modal>&times;</button></div>
        <div class="icm-modal-body">
            @if($course->modules()->count()===0)
                <p>Create a module first.</p>
            @else
            <form method="POST" enctype="multipart/form-data" action="{{ route('instructor.courses.lessons.store',[$course,$course->modules()->orderBy('position')->first()]) }}" class="icm-form" id="lessonForm">@csrf
                <div><label>Module</label><select id="lessonModule">@foreach($course->modules()->orderBy('position')->get() as $module)<option value="{{ route('instructor.courses.lessons.store',[$course,$module]) }}">{{ $module->title }}</option>@endforeach</select></div>
                <div><label>Lesson title</label><input name="title" placeholder="Enter lesson title" required></div>
                <div><label>Content type</label><select name="content_type"><option value="text">Text</option><option value="video">Video</option><option value="file">File</option><option value="link">Link</option><option value="mixed">Mixed</option></select></div>
                <div><label>Estimated minutes</label><input type="number" min="1" name="estimated_minutes" placeholder="30"></div>
                <div><label>Position</label><input type="number" min="1" name="position" placeholder="e.g. 1"></div>
                <div><label>Video URL</label><input type="url" name="video_url" placeholder="https://..."></div>
                <div class="full"><label>External URL</label><input type="url" name="external_url" placeholder="https://..."></div>
                <div class="full"><label>Upload resource</label><input type="file" name="resource_file"><small class="icm-muted">PDF, Office document, image, audio, video or ZIP up to 50 MB.</small></div>
                <div class="full"><label>Content</label><textarea name="content" placeholder="Enter lesson content"></textarea></div>
                <label class="full"><input type="checkbox" name="is_published" value="1"> Publish lesson</label>
                <div class="full"><button class="btn btn-primary"><i class="fas fa-save"></i> Save Lesson</button></div>
            </form>
            @endif
        </div>
    </div>
</div>

<div class="icm-modal" id="assessmentModal" aria-hidden="true">
    <div class="icm-modal-dialog">
        <div class="icm-modal-head"><h3>Add Assignment, Quiz or Exam</h3><button type="button" class="icm-modal-close" data-close-modal>&times;</button></div>
        <div class="icm-modal-body">
            <form method="POST" enctype="multipart/form-data" action="{{ route('instructor.courses.assessments.store',$course) }}" class="icm-form">@csrf
                <div><label>Title</label><input name="title" placeholder="Assessment title" required></div>
                <div><label>Type</label><select name="type"><option value="assignment">Assignment</option><option value="quiz">Quiz</option><option value="exam">Exam</option></select></div>
                <div><label>Pass mark</label><input type="number" min="0" max="100" name="pass_mark" value="50" required></div>
                <div><label>Max attempts</label><input type="number" min="1" max="20" name="max_attempts" value="1" required></div>
                <div><label>Opens at</label><input type="datetime-local" name="opens_at"></div>
                <div><label>Due at</label><input type="datetime-local" name="due_at"></div>
                <div class="full"><label>Upload assessment file</label><input type="file" name="assessment_file"><small class="icm-muted">Optional. Upload instructions, question paper or supporting document.</small></div>
                <div class="full"><label>Instructions</label><textarea name="instructions" placeholder="Instructions for participants"></textarea></div>
                <label class="full"><input type="checkbox" name="is_published" value="1"> Publish assessment</label>
                <div class="full"><button class="btn btn-primary"><i class="fas fa-save"></i> Save Assessment</button></div>
            </form>
        </div>
    </div>
</div>

<div class="icm-modal" id="questionModal" aria-hidden="true">
    <div class="icm-modal-dialog">
        <div class="icm-modal-head"><h3 id="questionModalTitle">Add Question</h3><button type="button" class="icm-modal-close" data-close-modal>&times;</button></div>
        <div class="icm-modal-body">
            <form method="POST" action="" class="icm-form" id="questionForm">@csrf
                <div><label>Question type</label><select name="question_type"><option value="multiple_choice">Multiple choice</option><option value="true_false">True / False</option><option value="short_text">Short text</option><option value="long_text">Long text</option></select></div>
                <div><label>Marks</label><input type="number" step="0.1" min="0.1" name="marks" value="1" required></div>
                <div class="full"><label>Question</label><textarea name="question_text" placeholder="Enter question" required></textarea></div>
                <div class="full"><label>Options</label><textarea name="options_text" placeholder="For multiple choice: one option per line"></textarea></div>
                <div class="full"><label>Correct value</label><input name="correct_value" placeholder="Correct option/value"></div>
                <div class="full"><button class="btn btn-primary"><i class="fas fa-plus"></i> Add Question</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    const requestedTab=new URLSearchParams(window.location.search).get('tab');

    document.querySelectorAll('.icm-tab-btn[data-tab]').forEach(btn=>btn.addEventListener('click',function(){
        document.querySelectorAll('.icm-tab-btn[data-tab]').forEach(x=>x.classList.remove('active'));
        document.querySelectorAll('.icm-pane').forEach(x=>x.classList.remove('active'));
        btn.classList.add('active');
        document.querySelector('[data-pane="'+btn.dataset.tab+'"]')?.classList.add('active');
        const url=new URL(window.location.href);
        url.searchParams.set('tab',btn.dataset.tab);
        history.replaceState(null,'',url);
    }));

    if(requestedTab){
        const b=document.querySelector('.icm-tab-btn[data-tab="'+requestedTab+'"]');
        if(b){ b.click(); }
    }

    const moduleSelect=document.getElementById('lessonModule');
    const lessonForm=document.getElementById('lessonForm');
    if(moduleSelect&&lessonForm){
        moduleSelect.addEventListener('change',()=>lessonForm.action=moduleSelect.value);
        lessonForm.action=moduleSelect.value;
    }

    function openModal(id){
        const modal=document.getElementById(id);
        if(modal){ modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); }
    }
    function closeModal(modal){
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden','true');
    }

    document.querySelectorAll('[data-open-modal]').forEach(btn=>btn.addEventListener('click',()=>openModal(btn.dataset.openModal)));
    document.querySelectorAll('[data-close-modal]').forEach(btn=>btn.addEventListener('click',()=>closeModal(btn.closest('.icm-modal'))));
    document.querySelectorAll('.icm-modal').forEach(modal=>modal.addEventListener('click',e=>{if(e.target===modal)closeModal(modal);}));

    const questionBase=@json(route('instructor.courses.assessments.questions.store',[$course,'__ASSESSMENT__']));
    document.querySelectorAll('[data-open-question]').forEach(btn=>btn.addEventListener('click',function(){
        const form=document.getElementById('questionForm');
        form.action=questionBase.replace('__ASSESSMENT__',btn.dataset.openQuestion);
        document.getElementById('questionModalTitle').textContent='Add Question: '+btn.dataset.assessmentTitle;
        openModal('questionModal');
    }));

    document.addEventListener('keydown',e=>{
        if(e.key==='Escape'){
            document.querySelectorAll('.icm-modal.open').forEach(closeModal);
        }
    });
});
</script>
@endsection
