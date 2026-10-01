@extends('layouts.admin')

@section('title', $course->title.' | Instructor Workspace')

@section('content')
@php
    $activeTab = request('tab', 'overview');

    $tabs = [
        'overview' => ['Overview', 'fa-gauge-high'],
        'modules' => ['Modules', 'fa-layer-group'],
        'lessons' => ['Lessons', 'fa-book-open'],
        'materials' => ['Learning Materials', 'fa-folder-open'],
        'assignments' => ['Assignments', 'fa-list-check'],
        'quizzes' => ['Quizzes', 'fa-circle-question'],
        'exams' => ['Exams', 'fa-file-signature'],
        'submissions' => ['Submissions', 'fa-file-circle-check'],
        'extensions' => ['Extension Requests', 'fa-clock'],
        'participants' => ['Participants', 'fa-users'],
        'progress' => ['Progress', 'fa-chart-line'],
        'announcements' => ['Announcements', 'fa-bullhorn'],
        'settings' => ['Settings', 'fa-gear'],
    ];

    $allModules = $course->modules()->orderBy('position')->get();
    $pendingExtensionCount = (int) ($pendingExtensionCount ?? 0);
@endphp

<style>
.icm-header-actions{display:flex;gap:10px;flex-wrap:wrap}
.icm-stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:18px}
.icm-stat{background:#fff;border:1px solid #e5e7eb;border-left:4px solid #800000;border-radius:12px;padding:15px}
.icm-stat small{display:block;color:#667085;font-weight:700}.icm-stat strong{display:block;font-size:1.3rem;margin-top:4px}
.icm-tabs{display:flex;gap:7px;overflow:auto;padding:8px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;margin-bottom:18px}
.icm-tabs a{padding:9px 12px;border-radius:8px;color:#475467;text-decoration:none;font-weight:700;white-space:nowrap}
.icm-tabs a.active{background:#800000;color:#fff}
.icm-panel{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;margin-bottom:18px}
.icm-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.icm-panel-head h2,.icm-panel-head h3{margin:0}
.icm-muted{color:#667085;font-size:.9rem}
.icm-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.icm-form-grid .full{grid-column:1/-1}
.icm-form-grid input,.icm-form-grid select,.icm-form-grid textarea,
.icm-filter input,.icm-filter select{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d0d5dd;border-radius:8px;background:#fff}
.icm-form-grid textarea{min-height:90px}
.icm-filter{display:grid;grid-template-columns:2fr repeat(3,minmax(150px,1fr)) auto;gap:10px;margin-bottom:15px}
.icm-card-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.icm-card{border:1px solid #e5e7eb;border-radius:12px;padding:15px}
.icm-card h3{margin:5px 0}
.icm-chip{display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;background:#f2f4f7;font-size:.75rem;font-weight:700}
.icm-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.icm-details{margin-top:12px;border-top:1px solid #eaecf0;padding-top:12px}
.icm-progress{height:8px;background:#eaecf0;border-radius:999px;overflow:hidden}
.icm-progress span{display:block;height:100%;background:#800000}
.icm-badge{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;margin-left:4px;border-radius:999px;background:#b42318;color:#fff;font-size:.72rem;font-weight:800;line-height:1}
.icm-tabs a.active .icm-badge{background:#fff;color:#800000}
.icm-status-pending{background:#fef0c7;color:#93370d}
.icm-status-approved{background:#d1fadf;color:#05603a}
.icm-status-rejected{background:#fee4e2;color:#b42318}
.icm-notice{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:12px 14px;margin-bottom:14px;border:1px solid #fedf89;background:#fffaeb;border-radius:10px}
.table-responsive{overflow:auto}
.admin-table{width:100%;border-collapse:collapse}
.admin-table th,.admin-table td{padding:10px;border-bottom:1px solid #eaecf0;text-align:left;vertical-align:top}
.admin-table th{font-size:.78rem;color:#667085;text-transform:uppercase;letter-spacing:.03em}
@media(max-width:1200px){.icm-stats{grid-template-columns:repeat(3,1fr)}.icm-filter{grid-template-columns:repeat(2,1fr)}}
@media(max-width:800px){.icm-stats,.icm-card-grid,.icm-form-grid,.icm-filter{grid-template-columns:1fr}.icm-form-grid .full{grid-column:auto}}
</style>

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Instructor / Trainer Workspace</span>
        <h1>{{ $course->title }}</h1>
        <p>Manage assigned course content, assessments, announcements, participants and progress.</p>
    </div>
    <div class="icm-header-actions">
        @if(Route::has('instructor.attendance.create'))
            <a href="{{ route('instructor.attendance.create', $course) }}" class="btn btn-outline"><i class="fas fa-user-check"></i> Attendance</a>
        @endif
        <a href="{{ route('admin.my-courses') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> My Courses</a>
    </div>
</div>

<div class="icm-stats">
    <div class="icm-stat"><small>Modules</small><strong>{{ number_format($stats['modules']) }}</strong></div>
    <div class="icm-stat"><small>Lessons</small><strong>{{ number_format($stats['lessons']) }}</strong></div>
    <div class="icm-stat"><small>Assignments</small><strong>{{ number_format($stats['assignments']) }}</strong></div>
    <div class="icm-stat"><small>Quizzes</small><strong>{{ number_format($stats['quizzes']) }}</strong></div>
    <div class="icm-stat"><small>Exams</small><strong>{{ number_format($stats['exams']) }}</strong></div>
    <div class="icm-stat"><small>Participants</small><strong>{{ number_format($stats['participants']) }}</strong></div>
</div>

<nav class="icm-tabs" aria-label="Course workspace">
    @foreach($tabs as $key => [$label, $icon])
        <a href="{{ route('instructor.courses.manage', ['course' => $course, 'tab' => $key]) }}"
           class="{{ $activeTab === $key ? 'active' : '' }}">
            <i class="fas {{ $icon }}"></i> {{ $label }}
            @if(in_array($key, ['submissions', 'extensions'], true) && $pendingExtensionCount > 0)
                <span class="icm-badge" title="Pending extension requests">{{ $pendingExtensionCount }}</span>
            @endif
        </a>
    @endforeach
</nav>

@if($activeTab === 'overview')
<section class="icm-panel">
    <div class="icm-panel-head">
        <div><h2>Course Overview</h2><p class="icm-muted">Quick access to the key delivery areas for this assigned course.</p></div>
    </div>
    <div class="icm-card-grid">
        @foreach([
            ['modules','Modules','Create, edit, publish and reorder modules.','fa-layer-group'],
            ['lessons','Lessons','Create and maintain lesson content and resources.','fa-book-open'],
            ['assignments','Assignments','Create assignments, due dates and files.','fa-list-check'],
            ['quizzes','Quizzes','Create quizzes, questions, attempts and pass marks.','fa-circle-question'],
            ['exams','Exams','Create timed exams with marks and pass requirements.','fa-file-signature'],
            ['progress','Participant Progress','Review learning, assessment and attendance progress.','fa-chart-line'],
        ] as [$tab,$title,$copy,$icon])
        <a class="icm-card" style="text-decoration:none;color:inherit" href="{{ route('instructor.courses.manage',['course'=>$course,'tab'=>$tab]) }}">
            <i class="fas {{ $icon }}"></i>
            <h3>{{ $title }}</h3>
            <p class="icm-muted">{{ $copy }}</p>
        </a>
        @endforeach
    </div>
</section>
@endif

@if($activeTab === 'modules')
<section class="icm-panel">
    <div class="icm-panel-head"><div><h2>Modules</h2><p class="icm-muted">Create, edit, publish and reorder course modules.</p></div></div>

    <form method="POST" action="{{ route('instructor.courses.modules.store',$course) }}" class="icm-form-grid">
        @csrf
        <div><label>Title</label><input name="title" required></div>
        <div><label>Position</label><input type="number" name="position" min="1"></div>
        <div class="full"><label>Description</label><textarea name="description"></textarea></div>
        <label><input type="checkbox" name="is_published" value="1"> Published</label>
        <div><button class="btn btn-primary"><i class="fas fa-plus"></i> Add Module</button></div>
    </form>
</section>

<section class="icm-panel">
    <div class="icm-card-grid">
        @forelse($modules as $module)
        <article class="icm-card">
            <span class="icm-chip">Module {{ $module->position }}</span>
            <h3>{{ $module->title }}</h3>
            <p class="icm-muted">{{ $module->lessons_count }} lesson(s) · {{ $module->is_published ? 'Published' : 'Draft' }}</p>
            @if($module->description)<p>{{ $module->description }}</p>@endif
            <details class="icm-details">
                <summary>Edit module</summary>
                <form method="POST" action="{{ route('instructor.courses.modules.update',[$course,$module]) }}" class="icm-form-grid" style="margin-top:12px">
                    @csrf @method('PUT')
                    <div><label>Title</label><input name="title" value="{{ $module->title }}" required></div>
                    <div><label>Position</label><input type="number" name="position" min="1" value="{{ $module->position }}" required></div>
                    <div class="full"><label>Description</label><textarea name="description">{{ $module->description }}</textarea></div>
                    <label><input type="checkbox" name="is_published" value="1" @checked($module->is_published)> Published</label>
                    <div><button class="btn btn-primary btn-sm">Save Module</button></div>
                </form>
            </details>
            <form method="POST" action="{{ route('instructor.courses.modules.destroy',[$course,$module]) }}" onsubmit="return confirm('Delete this module and its lessons?')" style="margin-top:10px">
                @csrf @method('DELETE')
                <button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i> Delete</button>
            </form>
        </article>
        @empty
        <div class="icm-card">No modules found.</div>
        @endforelse
    </div>
    <div style="margin-top:15px">{{ $modules->appends(['tab'=>'modules'])->links() }}</div>
</section>
@endif

@if($activeTab === 'lessons')
<section class="icm-panel">
    <div class="icm-panel-head"><div><h2>Lessons</h2><p class="icm-muted">Create lessons with text, video, links and downloadable files.</p></div></div>

    <form method="POST" enctype="multipart/form-data" action="{{ $allModules->isNotEmpty() ? route('instructor.courses.lessons.store',[$course,$allModules->first()]) : '#' }}" class="icm-form-grid" id="lessonCreateForm">
        @csrf
        <div>
            <label>Module</label>
            <select id="lessonModule" required>
                <option value="">Select module</option>
                @foreach($allModules as $module)<option value="{{ $module->id }}">{{ $module->title }}</option>@endforeach
            </select>
        </div>
        <div><label>Lesson title</label><input name="title" required></div>
        <div><label>Content type</label><select name="content_type" required>@foreach(['text','video','file','link','mixed'] as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></div>
        <div><label>Estimated minutes</label><input type="number" name="estimated_minutes" min="1"></div>
        <div><label>Position</label><input type="number" name="position" min="1"></div>
        <div><label>Video URL</label><input type="url" name="video_url"></div>
        <div><label>External URL</label><input type="url" name="external_url"></div>
        <div><label>Resource file</label><input type="file" name="resource_file"></div>
        <div class="full"><label>Lesson content</label><textarea name="content"></textarea></div>
        <label><input type="checkbox" name="is_published" value="1"> Published</label>
        <div><button class="btn btn-primary" @disabled($allModules->isEmpty())><i class="fas fa-plus"></i> Add Lesson</button></div>
    </form>
</section>

<section class="icm-panel">
    <form method="GET" class="icm-filter">
        <input type="hidden" name="tab" value="lessons">
        <input name="lesson_search" value="{{ request('lesson_search') }}" placeholder="Search lessons">
        <select name="module_id"><option value="">All modules</option>@foreach($allModules as $module)<option value="{{ $module->id }}" @selected((string)request('module_id')===(string)$module->id)>{{ $module->title }}</option>@endforeach</select>
        <select name="lesson_type"><option value="">All types</option>@foreach(['text','video','file','link','mixed'] as $type)<option value="{{ $type }}" @selected(request('lesson_type')===$type)>{{ ucfirst($type) }}</option>@endforeach</select>
        <span></span>
        <button class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
    </form>

    <div class="icm-card-grid">
        @forelse($lessons as $lesson)
        <article class="icm-card">
            <span class="icm-chip">{{ ucfirst($lesson->content_type) }}</span>
            <h3>{{ $lesson->title }}</h3>
            <p class="icm-muted">{{ $lesson->module?->title }} · {{ $lesson->is_published ? 'Published' : 'Draft' }}</p>

            <div class="icm-actions">
                @if($lesson->file_path)<a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.lessons.file',[$course,$lesson->module,$lesson,'preview'=>1]) }}" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Preview</a> <a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.lessons.file',[$course,$lesson->module,$lesson]) }}"><i class="fas fa-download"></i> File</a>@endif
                @if($lesson->video_url)<a class="btn btn-outline btn-sm" href="{{ $lesson->video_url }}" target="_blank"><i class="fas fa-video"></i> Video</a>@endif
                @if($lesson->external_url)<a class="btn btn-outline btn-sm" href="{{ $lesson->external_url }}" target="_blank"><i class="fas fa-link"></i> Link</a>@endif
            </div>

            <details class="icm-details">
                <summary>Edit lesson</summary>
                <form method="POST" enctype="multipart/form-data" action="{{ route('instructor.courses.lessons.update',[$course,$lesson->module,$lesson]) }}" class="icm-form-grid" style="margin-top:12px">
                    @csrf @method('PUT')
                    <div><label>Title</label><input name="title" value="{{ $lesson->title }}" required></div>
                    <div><label>Type</label><select name="content_type">@foreach(['text','video','file','link','mixed'] as $type)<option value="{{ $type }}" @selected($lesson->content_type===$type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
                    <div><label>Estimated minutes</label><input type="number" name="estimated_minutes" min="1" value="{{ $lesson->estimated_minutes }}"></div>
                    <div><label>Position</label><input type="number" name="position" min="1" value="{{ $lesson->position }}"></div>
                    <div><label>Video URL</label><input type="url" name="video_url" value="{{ $lesson->video_url }}"></div>
                    <div><label>External URL</label><input type="url" name="external_url" value="{{ $lesson->external_url }}"></div>
                    <div><label>Replace file</label><input type="file" name="resource_file"></div>
                    <div><label><input type="checkbox" name="remove_file" value="1"> Remove existing file</label></div>
                    <div class="full"><label>Content</label><textarea name="content">{{ $lesson->content }}</textarea></div>
                    <label><input type="checkbox" name="is_published" value="1" @checked($lesson->is_published)> Published</label>
                    <div><button class="btn btn-primary btn-sm">Save Lesson</button></div>
                </form>
            </details>

            <form method="POST" action="{{ route('instructor.courses.lessons.destroy',[$course,$lesson->module,$lesson]) }}" onsubmit="return confirm('Delete this lesson?')" style="margin-top:10px">
                @csrf @method('DELETE')
                <button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i> Delete</button>
            </form>
        </article>
        @empty
        <div class="icm-card">No lessons found.</div>
        @endforelse
    </div>
    <div style="margin-top:15px">{{ $lessons->appends(['tab'=>'lessons'])->links() }}</div>
</section>
@endif

@if($activeTab === 'materials')
<section class="icm-panel">
    <div class="icm-panel-head"><div><h2>Learning Materials</h2><p class="icm-muted">Files, links and media attached to lessons.</p></div></div>
    <div class="icm-card-grid">
        @forelse($course->modules()->with('lessons')->orderBy('position')->get()->flatMap->lessons->filter(fn($lesson)=>$lesson->file_path || $lesson->video_url || $lesson->external_url) as $lesson)
            <article class="icm-card">
                <h3>{{ $lesson->title }}</h3>
                <p class="icm-muted">{{ $lesson->module?->title }}</p>
                <div class="icm-actions">
                    @if($lesson->file_path)<a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.lessons.file',[$course,$lesson->module,$lesson,'preview'=>1]) }}" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Preview</a> <a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.lessons.file',[$course,$lesson->module,$lesson]) }}"><i class="fas fa-download"></i> Download</a>@endif
                    @if($lesson->video_url)<a class="btn btn-outline btn-sm" href="{{ $lesson->video_url }}" target="_blank"><i class="fas fa-video"></i> Video</a>@endif
                    @if($lesson->external_url)<a class="btn btn-outline btn-sm" href="{{ $lesson->external_url }}" target="_blank"><i class="fas fa-link"></i> Resource</a>@endif
                </div>
            </article>
        @empty
            <div class="icm-card">No learning materials are attached yet.</div>
        @endforelse
    </div>
</section>
@endif

@foreach(['assignment'=>'assignments','quiz'=>'quizzes','exam'=>'exams'] as $assessmentType => $tabName)
@if($activeTab === $tabName)
<section class="icm-panel">
    <div class="icm-panel-head">
        <div><h2>{{ ucfirst($tabName) }}</h2><p class="icm-muted">Create and manage {{ $tabName }} for this course.</p></div>
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ route('instructor.courses.assessments.store',$course) }}" class="icm-form-grid">
        @csrf
        <input type="hidden" name="type" value="{{ $assessmentType }}">
        <div><label>Title</label><input name="title" required></div>
        <div><label>Module (optional)</label><select name="course_module_id"><option value="">Course-wide</option>@foreach($allModules as $module)<option value="{{ $module->id }}">{{ $module->title }}</option>@endforeach</select></div>
        <div><label>Pass mark (%)</label><input type="number" name="pass_mark" value="{{ $course->pass_mark ?? 50 }}" min="0" max="100" step="0.01" required></div>
        <div><label>Maximum attempts</label><input type="number" name="max_attempts" value="1" min="1" max="20" required></div>
        <div><label>Opens at</label><input type="datetime-local" name="opens_at"></div>
        <div><label>Due / closes at</label><input type="datetime-local" name="due_at"></div>
        <div><label>Duration minutes</label><input type="number" name="duration_minutes" min="1"></div>
        <div><label>Total marks</label><input type="number" name="total_marks" min="0" step="0.01"></div>
        <div><label>Attachment</label><input type="file" name="assessment_file"></div>
        <label><input type="checkbox" name="is_published" value="1"> Published</label>
        <div class="full"><label>Instructions</label><textarea name="instructions"></textarea></div>
        <div><button class="btn btn-primary"><i class="fas fa-plus"></i> Add {{ ucfirst($assessmentType) }}</button></div>
    </form>
</section>

<section class="icm-panel">
    <div class="icm-card-grid">
        @forelse($course->assessments()->where('type',$assessmentType)->withCount(['questions','attempts'])->latest()->get() as $assessment)
        <article class="icm-card">
            <span class="icm-chip">{{ ucfirst($assessment->type) }}</span>
            <h3>{{ $assessment->title }}</h3>
            <p class="icm-muted">
                {{ $assessment->questions_count }} question(s) · {{ $assessment->attempts_count }} attempt(s)
                · {{ $assessment->is_published ? 'Published' : 'Draft' }}
            </p>
            @if($assessment->duration_minutes)<p><strong>Duration:</strong> {{ $assessment->duration_minutes }} minutes</p>@endif
            @if($assessment->total_marks !== null)<p><strong>Total marks:</strong> {{ number_format((float)$assessment->total_marks,1) }}</p>@endif
            @if($assessment->due_at)<p><strong>Closes:</strong> {{ $assessment->due_at->format('d M Y H:i') }}</p>@endif

            <details class="icm-details">
                <summary>Edit {{ $assessmentType }}</summary>
                <form method="POST" enctype="multipart/form-data" action="{{ route('instructor.courses.assessments.update',[$course,$assessment]) }}" class="icm-form-grid" style="margin-top:12px">
                    @csrf @method('PUT')
                    <input type="hidden" name="type" value="{{ $assessmentType }}">
                    <div><label>Title</label><input name="title" value="{{ $assessment->title }}" required></div>
                    <div><label>Module</label><select name="course_module_id"><option value="">Course-wide</option>@foreach($allModules as $module)<option value="{{ $module->id }}" @selected((int)$assessment->course_module_id===(int)$module->id)>{{ $module->title }}</option>@endforeach</select></div>
                    <div><label>Pass mark</label><input type="number" name="pass_mark" min="0" max="100" step=".01" value="{{ $assessment->pass_mark }}" required></div>
                    <div><label>Attempts</label><input type="number" name="max_attempts" min="1" max="20" value="{{ $assessment->max_attempts }}" required></div>
                    <div><label>Opens at</label><input type="datetime-local" name="opens_at" value="{{ $assessment->opens_at?->format('Y-m-d\TH:i') }}"></div>
                    <div><label>Due / closes</label><input type="datetime-local" name="due_at" value="{{ $assessment->due_at?->format('Y-m-d\TH:i') }}"></div>
                    <div><label>Duration minutes</label><input type="number" name="duration_minutes" min="1" value="{{ $assessment->duration_minutes }}"></div>
                    <div><label>Total marks</label><input type="number" name="total_marks" min="0" step=".01" value="{{ $assessment->total_marks }}"></div>
                    <div><label>Replace attachment</label><input type="file" name="assessment_file"></div>
                    <div><label><input type="checkbox" name="remove_attachment" value="1"> Remove attachment</label></div>
                    <div class="full"><label>Instructions</label><textarea name="instructions">{{ $assessment->instructions }}</textarea></div>
                    <label><input type="checkbox" name="is_published" value="1" @checked($assessment->is_published)> Published</label>
                    <div><button class="btn btn-primary btn-sm">Save</button></div>
                </form>
            </details>

            @if(in_array($assessmentType,['quiz','exam'],true))
            <details class="icm-details">
                <summary>Add question</summary>
                <form method="POST" action="{{ route('instructor.courses.assessments.questions.store',[$course,$assessment]) }}" class="icm-form-grid" style="margin-top:12px">
                    @csrf
                    <div><label>Question type</label><select name="question_type">@foreach(['multiple_choice'=>'Multiple choice','true_false'=>'True / False','short_text'=>'Short text','long_text'=>'Long text'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label>Marks</label><input type="number" name="marks" min=".1" step=".1" value="1" required></div>
                    <div class="full"><label>Question</label><textarea name="question_text" required></textarea></div>
                    <div class="full"><label>Options (one per line)</label><textarea name="options_text"></textarea></div>
                    <div><label>Correct value</label><input name="correct_value"></div>
                    <div><button class="btn btn-primary btn-sm">Add Question</button></div>
                </form>
            </details>
            @endif

            <form method="POST" action="{{ route('instructor.courses.assessments.destroy',[$course,$assessment]) }}" onsubmit="return confirm('Delete this {{ $assessmentType }}?')" style="margin-top:10px">
                @csrf @method('DELETE')
                <button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i> Delete</button>
            </form>
        </article>
        @empty
        <div class="icm-card">No {{ $tabName }} created yet.</div>
        @endforelse
    </div>
</section>
@endif
@endforeach

@if($activeTab === 'submissions')
<section class="icm-panel">
    <div class="icm-panel-head"><div><h2>Submissions</h2><p class="icm-muted">Review participant submissions and record marks and feedback.</p></div></div>
    @if($pendingExtensionCount > 0)
        <div class="icm-notice">
            <span><i class="fas fa-clock"></i> <strong>{{ $pendingExtensionCount }}</strong> pending extension {{ \Illuminate\Support\Str::plural('request', $pendingExtensionCount) }} waiting for review.</span>
            <a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.manage', ['course' => $course, 'tab' => 'extensions']) }}">Review requests</a>
        </div>
    @endif
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Participant</th><th>Assessment</th><th>Status</th><th>Submitted</th><th>Review</th></tr></thead>
            <tbody>
            @forelse($submissions as $attempt)
                <tr>
                    <td>{{ $attempt->user?->name }}<br><small>{{ $attempt->user?->email }}</small></td>
                    <td>{{ $attempt->assessment?->title }}<br><small>{{ ucfirst($attempt->assessment?->type ?? '') }}</small></td>
                    <td>{{ ucfirst($attempt->status) }}</td>
                    <td>{{ $attempt->submitted_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td>
                        @if($attempt->submission_file_path)
                            <a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.submissions.file',[$course,$attempt,'preview'=>1]) }}" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Preview</a>
                            <a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.submissions.file',[$course,$attempt]) }}"><i class="fas fa-download"></i> File</a>
                        @endif
                        <details class="icm-details">
                            <summary>Grade / feedback</summary>
                            <form method="POST" action="{{ route('instructor.courses.submissions.review',[$course,$attempt]) }}" class="icm-form-grid" style="min-width:360px;margin-top:10px">
                                @csrf @method('PUT')
                                <div><label>Score</label><input type="number" name="score" min="0" step=".01" value="{{ $attempt->score }}"></div>
                                <div><label>Percentage</label><input type="number" name="percentage" min="0" max="100" step=".01" value="{{ $attempt->percentage }}"></div>
                                <div><label>Status</label><select name="status"><option value="submitted" @selected($attempt->status==='submitted')>Submitted</option><option value="graded" @selected($attempt->status==='graded')>Graded</option></select></div>
                                <div class="full"><label>Feedback</label><textarea name="instructor_feedback">{{ $attempt->instructor_feedback }}</textarea></div>
                                <div><button class="btn btn-primary btn-sm">Save Review</button></div>
                            </form>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No submissions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:15px">{{ $submissions->appends(['tab'=>'submissions'])->links() }}</div>
</section>
@endif

@if($activeTab === 'extensions')
<section class="icm-panel">
    <div class="icm-panel-head">
        <div>
            <h2>Extension Requests <span class="icm-chip icm-status-pending">{{ $pendingExtensionCount }} pending</span></h2>
            <p class="icm-muted">Participants who missed, or are about to miss, an assignment deadline. Approving sets a new due date for that participant only.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Participant</th><th>Assignment</th><th>Original due</th><th>Reason</th><th>Requested date</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse(($extensionRequests ?? collect()) as $extension)
                @php
                    $defaultDue = $extension->requested_due_at && $extension->requested_due_at->isFuture()
                        ? $extension->requested_due_at
                        : now()->addDays(3);
                @endphp
                <tr>
                    <td>{{ $extension->user?->name }}<br><small>{{ $extension->user?->email }}</small></td>
                    <td>{{ $extension->assessment?->title }}<br><small>{{ ucfirst($extension->assessment?->type ?? '') }}</small></td>
                    <td>{{ $extension->assessment?->due_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td style="max-width:320px;white-space:pre-line">{{ $extension->reason }}</td>
                    <td>{{ $extension->requested_due_at?->format('d M Y H:i') ?? '—' }}<br><small class="icm-muted">Sent {{ $extension->created_at?->format('d M Y H:i') }}</small></td>
                    <td>
                        <span class="icm-chip icm-status-{{ $extension->status }}">{{ ucfirst($extension->status) }}</span>
                        @if(! $extension->isPending())
                            <br><small class="icm-muted">
                                {{ $extension->reviewer?->name ?? 'Reviewed' }} · {{ $extension->reviewed_at?->format('d M Y H:i') }}
                                @if($extension->approved_due_at)<br>New due: {{ $extension->approved_due_at->format('d M Y H:i') }}@endif
                                @if($extension->reviewer_note)<br>Note: {{ $extension->reviewer_note }}@endif
                            </small>
                        @endif
                    </td>
                    <td>
                        @if($extension->isPending())
                            <details class="icm-details" open>
                                <summary>Approve</summary>
                                <form method="POST" action="{{ route('instructor.courses.extension-requests.approve', [$course, $extension]) }}" class="icm-form-grid" style="min-width:320px;margin-top:10px">
                                    @csrf
                                    <div class="full"><label>New due date</label><input type="datetime-local" name="approved_due_at" required min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ $defaultDue->format('Y-m-d\TH:i') }}"></div>
                                    <div class="full"><label>Note (optional)</label><textarea name="reviewer_note" maxlength="1000" style="min-height:60px"></textarea></div>
                                    <div><button class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Approve</button></div>
                                </form>
                            </details>
                            <details class="icm-details">
                                <summary>Reject</summary>
                                <form method="POST" action="{{ route('instructor.courses.extension-requests.reject', [$course, $extension]) }}" class="icm-form-grid" style="min-width:320px;margin-top:10px" onsubmit="return confirm('Reject this extension request?')">
                                    @csrf
                                    <div class="full"><label>Note to participant (optional)</label><textarea name="reviewer_note" maxlength="1000" style="min-height:60px"></textarea></div>
                                    <div><button class="btn btn-outline btn-sm"><i class="fas fa-xmark"></i> Reject</button></div>
                                </form>
                            </details>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No extension requests for this course.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($extensionRequests))
        <div style="margin-top:15px">{{ $extensionRequests->appends(['tab'=>'extensions'])->links() }}</div>
    @endif
</section>
@endif

@if($activeTab === 'participants')
<section class="icm-panel">
    <div class="icm-panel-head"><div><h2>Course Participants</h2><p class="icm-muted">View and update enrolment status and course progress.</p></div></div>
    <form method="GET" class="icm-filter">
        <input type="hidden" name="tab" value="participants">
        <input name="participant_search" value="{{ request('participant_search') }}" placeholder="Search name or email">
        <select name="participant_status"><option value="">All statuses</option>@foreach(['enrolled','in_progress','completed','withdrawn','failed'] as $status)<option value="{{ $status }}" @selected(request('participant_status')===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select>
        <select name="cohort_id"><option value="">All cohorts</option>@foreach($course->cohorts as $cohort)<option value="{{ $cohort->id }}" @selected((string)request('cohort_id')===(string)$cohort->id)>{{ $cohort->name }}</option>@endforeach</select>
        <span></span>
        <button class="btn btn-outline">Filter</button>
    </form>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Participant</th><th>Cohort</th><th>Status</th><th>Progress</th><th>Final Score</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($participants as $enrolment)
            <tr>
                <td>{{ $enrolment->user?->name }}<br><small>{{ $enrolment->user?->email }}</small></td>
                <td>{{ $enrolment->cohort?->name ?? '—' }}</td>
                <td>{{ ucfirst(str_replace('_',' ',$enrolment->status)) }}</td>
                <td>{{ number_format((float)$enrolment->progress_percent,1) }}%</td>
                <td>{{ $enrolment->final_score !== null ? number_format((float)$enrolment->final_score,1).'%' : '—' }}</td>
                <td>
                    <a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.participants.progress',[$course,$enrolment]) }}">View Progress</a>
                    <details class="icm-details">
                        <summary>Edit</summary>
                        <form method="POST" action="{{ route('instructor.courses.participants.update',[$course,$enrolment]) }}" class="icm-form-grid" style="min-width:360px;margin-top:10px">
                            @csrf @method('PUT')
                            <div><label>Status</label><select name="status">@foreach(['enrolled','in_progress','completed','withdrawn','failed'] as $status)<option value="{{ $status }}" @selected($enrolment->status===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
                            <div><label>Progress %</label><input type="number" name="progress_percent" min="0" max="100" step=".01" value="{{ $enrolment->progress_percent }}"></div>
                            <div><label>Final score</label><input type="number" name="final_score" min="0" max="100" step=".01" value="{{ $enrolment->final_score }}"></div>
                            <div><button class="btn btn-primary btn-sm">Save</button></div>
                        </form>
                    </details>
                </td>
            </tr>
            @empty
            <tr><td colspan="6">No participants found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:15px">{{ $participants->appends(['tab'=>'participants'])->links() }}</div>
</section>
@endif

@if($activeTab === 'progress')
<section class="icm-panel">
    <div class="icm-panel-head">
        <div><h2>Participant Progress</h2><p class="icm-muted">Lesson completion, modules, assessments, attendance and overall progress.</p></div>
        <a class="btn btn-outline" href="{{ route('instructor.courses.progress.export',$course) }}"><i class="fas fa-file-csv"></i> Export CSV</a>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Participant</th><th>Status</th><th>Lessons</th><th>Modules</th><th>Assignments</th><th>Quizzes</th><th>Exams</th><th>Avg Score</th><th>Attendance</th><th>Overall</th><th>Last Activity</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($progressRows as $row)
                <tr>
                    <td>{{ $row['participant'] }}<br><small>{{ $row['email'] }}</small></td>
                    <td>{{ ucfirst(str_replace('_',' ',$row['status'])) }}</td>
                    <td>{{ $row['lessons_completed'] }}/{{ $row['lessons_total'] }}</td>
                    <td>{{ $row['modules_completed'] }}/{{ $row['modules_total'] }}</td>
                    <td>{{ $row['assignment_progress'] }}</td>
                    <td>{{ $row['quiz_progress'] }}</td>
                    <td>{{ $row['exam_progress'] }}</td>
                    <td>{{ $row['average_score'] !== null ? number_format($row['average_score'],1).'%' : '—' }}</td>
                    <td>{{ $row['attendance'] }}</td>
                    <td>
                        <div class="icm-progress"><span style="width:{{ min(100,max(0,$row['overall_progress'])) }}%"></span></div>
                        <small>{{ number_format($row['overall_progress'],1) }}%</small>
                    </td>
                    <td>{{ $row['last_activity'] }}</td>
                    <td><a class="btn btn-outline btn-sm" href="{{ route('instructor.courses.participants.progress',[$course,$row['enrolment_id']]) }}">Details</a></td>
                </tr>
            @empty
                <tr><td colspan="12">No participant progress records.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif

@if($activeTab === 'announcements')
<section class="icm-panel">
    <div class="icm-panel-head"><div><h2>Announcements</h2><p class="icm-muted">Publish course-specific updates for enrolled participants.</p></div></div>
    <form method="POST" action="{{ route('instructor.courses.announcements.store',$course) }}" class="icm-form-grid">
        @csrf
        <div><label>Title</label><input name="title" required></div>
        <div><label>Publish at</label><input type="datetime-local" name="published_at"></div>
        <div><label>Expires at</label><input type="datetime-local" name="expires_at"></div>
        <div class="full"><label>Announcement</label><textarea name="body" required></textarea></div>
        <div><button class="btn btn-primary"><i class="fas fa-bullhorn"></i> Publish</button></div>
    </form>
</section>

<section class="icm-panel">
    <div class="icm-card-grid">
        @forelse($announcements as $announcement)
        <article class="icm-card">
            <h3>{{ $announcement->title }}</h3>
            <p class="icm-muted">Published {{ $announcement->published_at?->format('d M Y H:i') ?? 'immediately' }}</p>
            <p>{{ $announcement->body }}</p>
            <form method="POST" action="{{ route('instructor.courses.announcements.destroy',[$course,$announcement]) }}" onsubmit="return confirm('Delete this announcement?')">
                @csrf @method('DELETE')
                <button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i> Delete</button>
            </form>
        </article>
        @empty
        <div class="icm-card">No announcements found.</div>
        @endforelse
    </div>
    <div style="margin-top:15px">{{ $announcements->appends(['tab'=>'announcements'])->links() }}</div>
</section>
@endif

@if($activeTab === 'settings')
<section class="icm-panel">
    <div class="icm-panel-head"><div><h2>Course Settings</h2><p class="icm-muted">Update delivery information for this assigned course.</p></div></div>
    <form method="POST" action="{{ route('instructor.courses.update',$course) }}" class="icm-form-grid">
        @csrf @method('PUT')
        <div><label>Title</label><input name="title" value="{{ $course->title }}" required></div>
        <div><label>Delivery mode</label><select name="delivery_mode">@foreach(['online'=>'Online','in_person'=>'In person','blended'=>'Blended'] as $value=>$label)<option value="{{ $value }}" @selected($course->delivery_mode===$value)>{{ $label }}</option>@endforeach</select></div>
        <div><label>Start date</label><input type="date" name="start_date" value="{{ optional($course->start_date)->format('Y-m-d') }}"></div>
        <div><label>End date</label><input type="date" name="end_date" value="{{ optional($course->end_date)->format('Y-m-d') }}"></div>
        <div><label>Duration hours</label><input type="number" name="duration_hours" min="1" value="{{ $course->duration_hours }}"></div>
        <div><label>Pass mark</label><input type="number" name="pass_mark" min="0" max="100" step=".01" value="{{ $course->pass_mark }}" required></div>
        <div class="full"><label>Summary</label><textarea name="summary">{{ $course->summary }}</textarea></div>
        <div class="full"><label>Description</label><textarea name="description">{{ $course->description }}</textarea></div>
        <div><button class="btn btn-primary">Save Course</button></div>
    </form>
</section>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const moduleSelect = document.getElementById('lessonModule');
    const lessonForm = document.getElementById('lessonCreateForm');

    if (moduleSelect && lessonForm) {
        moduleSelect.addEventListener('change', () => {
            const moduleId = moduleSelect.value;
            if (!moduleId) return;

            const template = @json(url('/instructor/courses/'.$course->id.'/modules/__MODULE__/lessons'));
            lessonForm.action = template.replace('__MODULE__', moduleId);
        });
    }
});
</script>
@endsection
