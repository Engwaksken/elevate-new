@extends('layouts.admin')
@section('title','Courses | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Programme Delivery</span>
    <h1>Courses</h1>
    <p>Manage courses, modules, lessons, publication status and learning structure.</p>
</div>
<div class="admin-page-actions">
    <button type="button" class="btn btn-primary" data-modal-open="createCourseModal">
        <i class="fas fa-plus"></i> Add Course
    </button>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Total Courses','fa-graduation-cap'],
['published','Published','fa-circle-check'],
['draft','Draft','fa-pen'],
['archived','Archived','fa-box-archive']
] as [$key,$label,$icon])
<div class="admin-stat">
    <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
    <div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div>
</div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search title, code or summary...">
    </div>

    <select name="status">
        <option value="">All statuses</option>
        @foreach(['draft','published','archived'] as $status)
        <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>

    <select name="delivery_mode">
        <option value="">All delivery modes</option>
        @foreach(['online'=>'Online','in_person'=>'In person','blended'=>'Blended'] as $value=>$label)
        <option value="{{ $value }}" @selected(request('delivery_mode')===$value)>{{ $label }}</option>
        @endforeach
    </select>

    <select name="per_page">
        @foreach([10,20,25,50,100] as $n)
        <option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>
        @endforeach
    </select>

    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.elearning.courses.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>Course</th>
    <th>Mode</th>
    <th>Dates</th>
    <th>Modules</th>
    <th>Enrolments</th>
    <th>Assessments</th>
    <th>Status</th>
    <th class="table-actions">Actions</th>
</tr>
</thead>
<tbody>
@forelse($courses as $course)
<tr>
    <td><strong>{{ $course->title }}</strong><small class="admin-cell-hint">{{ $course->code ?: 'No course code' }}</small></td>
    <td>{{ ucfirst(str_replace('_',' ',$course->delivery_mode)) }}</td>
    <td>{{ optional($course->start_date)->format('d M Y') ?: '—' }} — {{ optional($course->end_date)->format('d M Y') ?: '—' }}</td>
    <td>{{ $course->modules_count }}</td>
    <td>{{ $course->enrolments_count }}</td>
    <td>{{ $course->assessments_count }}</td>
    <td><span class="status-chip {{ $course->status }}">{{ ucfirst($course->status) }}</span></td>
    <td class="table-actions">
        <div class="action-group">
            <button type="button" class="btn-icon" title="Edit course" data-modal-open="editCourse{{ $course->id }}"><i class="fas fa-pen"></i></button>
            <button type="button" class="btn-icon" title="Add module" data-modal-open="addModule{{ $course->id }}"><i class="fas fa-layer-group"></i></button>
            @if(Route::has('admin.elearning.assignments.edit'))
            <a class="btn-icon" title="Assignments" href="{{ route('admin.elearning.assignments.edit',$course) }}"><i class="fas fa-user-check"></i></a>
            @endif
        </div>
    </td>
</tr>

<tr>
<td colspan="8">
<details>
<summary><strong>Course structure</strong> · {{ $course->modules_count }} module(s)</summary>

<div class="course-structure">
@forelse($course->modules as $module)
<section class="course-module-card">
    <div class="course-module-head">
        <div>
            <strong>{{ $module->position ? $module->position.'. ' : '' }}{{ $module->title }}</strong>
            <small>{{ $module->is_published ? 'Published' : 'Draft' }} · {{ $module->lessons->count() }} lesson(s)</small>
        </div>

        <div class="action-group">
            <button type="button" class="btn-icon" title="Add lesson" data-modal-open="addLesson{{ $module->id }}"><i class="fas fa-plus"></i></button>
            <button type="button" class="btn-icon" title="Edit module" data-modal-open="editModule{{ $module->id }}"><i class="fas fa-pen"></i></button>
            <button type="button" class="btn-icon danger" title="Delete module" data-modal-open="deleteModule{{ $module->id }}"><i class="fas fa-trash"></i></button>
        </div>
    </div>

    @if($module->description)
    <p class="course-module-description">{{ $module->description }}</p>
    @endif

    <div class="course-lesson-list">
    @forelse($module->lessons as $lesson)
        <div class="course-lesson-row">
            <div class="course-lesson-main">
                <span class="course-lesson-icon"><i class="fas fa-file-lines"></i></span>
                <div>
                    <strong>{{ $lesson->position ? $lesson->position.'. ' : '' }}{{ $lesson->title }}</strong>
                    <small>{{ ucfirst($lesson->content_type) }} · {{ $lesson->estimated_minutes ? $lesson->estimated_minutes.' min' : 'No duration' }} · {{ $lesson->is_published ? 'Published' : 'Draft' }}</small>
                </div>
            </div>

            <div class="action-group">
                <button type="button" class="btn-icon" title="Edit lesson" data-modal-open="editLesson{{ $lesson->id }}"><i class="fas fa-pen"></i></button>
                <button type="button" class="btn-icon danger" title="Delete lesson" data-modal-open="deleteLesson{{ $lesson->id }}"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    @empty
        <div class="admin-empty compact">No lessons in this module.</div>
    @endforelse
    </div>
</section>
@empty
<div class="admin-empty">No modules have been added to this course.</div>
@endforelse
</div>
</details>
</td>
</tr>

@empty
<tr><td colspan="8"><div class="admin-empty">No courses found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $courses->links() }}</div>
</div>

<div class="eh-modal" id="createCourseModal" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
    <div><h2>Add Course</h2><p>Create a new ElevateHer360 learning course.</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.elearning.courses.store') }}">
@csrf
<input type="hidden" name="_modal" value="create">
<div class="eh-modal-body">
@include('admin.elearning.courses.partials.course-fields',['course'=>null])
</div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary">Create Course</button>
</div>
</form>
</div>
</div>

@foreach($courses as $course)
<div class="eh-modal" id="editCourse{{ $course->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
    <div><h2>Edit Course</h2><p>{{ $course->title }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.elearning.courses.update',$course) }}">
@csrf
@method('PUT')
<input type="hidden" name="_modal" value="edit-{{ $course->id }}">
<div class="eh-modal-body">
@include('admin.elearning.courses.partials.course-fields',['course'=>$course])
</div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary">Save Changes</button>
</div>
</form>
</div>
</div>

<div class="eh-modal" id="addModule{{ $course->id }}" aria-hidden="true">
<div class="eh-modal-dialog">
<div class="eh-modal-header">
    <div><h2>Add Module</h2><p>{{ $course->title }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.elearning.modules.store',$course) }}">
@csrf
<div class="eh-modal-body">
<div class="modal-grid">
    <div class="form-group full"><label>Module Title *</label><input name="title" required></div>
    <div class="form-group"><label>Position</label><input type="number" min="1" name="position" value="{{ max(1,$course->modules->count()+1) }}"></div>
    <div class="form-group"><label class="modal-check"><input type="checkbox" name="is_published" value="1"><span>Published</span></label></div>
    <div class="form-group full"><label>Description</label><textarea name="description"></textarea></div>
</div>
</div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary">Add Module</button>
</div>
</form>
</div>
</div>

@foreach($course->modules as $module)
<div class="eh-modal" id="editModule{{ $module->id }}" aria-hidden="true">
<div class="eh-modal-dialog">
<div class="eh-modal-header">
    <div><h2>Edit Module</h2><p>{{ $module->title }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.elearning.modules.update',[$course,$module]) }}">
@csrf
@method('PUT')
<div class="eh-modal-body">
<div class="modal-grid">
    <div class="form-group full"><label>Module Title *</label><input name="title" value="{{ $module->title }}" required></div>
    <div class="form-group"><label>Position</label><input type="number" min="1" name="position" value="{{ $module->position }}"></div>
    <div class="form-group"><label class="modal-check"><input type="checkbox" name="is_published" value="1" @checked($module->is_published)><span>Published</span></label></div>
    <div class="form-group full"><label>Description</label><textarea name="description">{{ $module->description }}</textarea></div>
</div>
</div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary">Save Module</button>
</div>
</form>
</div>
</div>

<div class="eh-modal" id="deleteModule{{ $module->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header">
    <div><h2>Delete Module?</h2><p>{{ $module->title }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>
<div class="eh-modal-body"><p>This permanently deletes the module. Existing lesson records under the module may also be affected by database relationships.</p></div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <form method="POST" action="{{ route('admin.elearning.modules.destroy',[$course,$module]) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete Module</button></form>
</div>
</div>
</div>

<div class="eh-modal" id="addLesson{{ $module->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
    <div><h2>Add Lesson</h2><p>{{ $module->title }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.elearning.lessons.store',$module) }}">
@csrf
<div class="eh-modal-body">
@include('admin.elearning.courses.partials.lesson-fields',['lesson'=>null,'module'=>$module])
</div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary">Add Lesson</button>
</div>
</form>
</div>
</div>

@foreach($module->lessons as $lesson)
<div class="eh-modal" id="editLesson{{ $lesson->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
    <div><h2>Edit Lesson</h2><p>{{ $lesson->title }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.elearning.lessons.update',[$module,$lesson]) }}">
@csrf
@method('PUT')
<div class="eh-modal-body">
@include('admin.elearning.courses.partials.lesson-fields',['lesson'=>$lesson,'module'=>$module])
</div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary">Save Lesson</button>
</div>
</form>
</div>
</div>

<div class="eh-modal" id="deleteLesson{{ $lesson->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header">
    <div><h2>Delete Lesson?</h2><p>{{ $lesson->title }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>
<div class="eh-modal-body"><p>This permanently deletes this lesson.</p></div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <form method="POST" action="{{ route('admin.elearning.lessons.destroy',[$module,$lesson]) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete Lesson</button></form>
</div>
</div>
</div>
@endforeach
@endforeach
@endforeach

@php($modalToOpen=old('_modal') ?: session('open_course_modal'))
@if($modalToOpen)
<script>
document.addEventListener('DOMContentLoaded', function(){
    const modal=@json($modalToOpen);
    const id=modal==='create' ? 'createCourseModal' : (modal.startsWith('edit-') ? 'editCourse'+modal.substring(5) : null);
    if(id) document.querySelector(`[data-modal-open="${id}"]`)?.click();
});
</script>
@endif

@endsection
