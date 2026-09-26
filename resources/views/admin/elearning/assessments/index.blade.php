@extends('layouts.admin')
@section('title','Assessments | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Programme Delivery</span><h1>Assessments — {{ $course->title }}</h1><p>Build quizzes, assignments and exams for this course.</p></div>
<div class="admin-page-actions">
<a href="{{ route('admin.elearning.courses.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Courses</a>
@if(Route::has('admin.elearning.gradebook.index'))<a href="{{ route('admin.elearning.gradebook.index',$course) }}" class="btn btn-outline"><i class="fas fa-table-list"></i> Gradebook</a>@endif
<button type="button" class="btn btn-primary" data-modal-open="createAssessmentModal"><i class="fas fa-plus"></i> New Assessment</button>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Total Assessments','fa-clipboard-question'],
['published','Published','fa-circle-check'],
['draft','Draft','fa-pen'],
['questions','Questions','fa-list-ol']
] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search assessment title..."></div>
<select name="type"><option value="">All types</option>@foreach(['quiz','assignment','exam'] as $type)<option value="{{ $type }}" @selected(request('type')===$type)>{{ ucfirst($type) }}</option>@endforeach</select>
<select name="published"><option value="">All publication states</option><option value="1" @selected(request('published')==='1')>Published</option><option value="0" @selected(request('published')==='0')>Draft</option></select>
<select name="per_page">@foreach([10,20,25,50] as $n)<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.elearning.assessments.index',$course) }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Assessment</th><th>Type</th><th>Questions</th><th>Pass Mark</th><th>Attempts</th><th>Window</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($assessments as $assessment)
<tr>
<td><strong>{{ $assessment->title }}</strong><small class="admin-cell-hint">{{ Str::limit($assessment->instructions,90) }}</small></td>
<td>{{ ucfirst($assessment->type) }}</td>
<td>{{ $assessment->questions_count }}</td>
<td>{{ number_format((float)$assessment->pass_mark,0) }}%</td>
<td>{{ $assessment->max_attempts }}</td>
<td>{{ optional($assessment->opens_at)->format('d M Y') ?: '—' }} — {{ optional($assessment->due_at)->format('d M Y') ?: '—' }}</td>
<td><span class="status-chip {{ $assessment->is_published ? 'published' : 'draft' }}">{{ $assessment->is_published ? 'Published' : 'Draft' }}</span></td>
<td class="table-actions"><a class="btn btn-outline btn-sm" href="{{ route('admin.elearning.assessments.edit',[$course,$assessment]) }}"><i class="fas fa-list-ol"></i> Questions</a></td>
</tr>
@empty<tr><td colspan="8"><div class="admin-empty">No assessments found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $assessments->links() }}</div>
</div>

<div class="eh-modal" id="createAssessmentModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>New Assessment</h2><p>{{ $course->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.elearning.assessments.store',$course) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Title *</label><input name="title" value="{{ old('title') }}" required></div>
<div class="form-group"><label>Type *</label><select name="type">@foreach(['quiz','assignment','exam'] as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></div>
<div class="form-group"><label>Pass Mark % *</label><input type="number" step=".01" min="0" max="100" name="pass_mark" value="{{ old('pass_mark',50) }}" required></div>
<div class="form-group"><label>Max Attempts *</label><input type="number" min="1" max="20" name="max_attempts" value="{{ old('max_attempts',1) }}" required></div>
<div class="form-group"><label>Opens At</label><input type="datetime-local" name="opens_at" value="{{ old('opens_at') }}"></div>
<div class="form-group"><label>Due At</label><input type="datetime-local" name="due_at" value="{{ old('due_at') }}"></div>
<div class="form-group full"><label>Instructions</label><textarea name="instructions" rows="5">{{ old('instructions') }}</textarea></div>
<div class="form-group full"><label class="modal-check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published'))><span>Published</span></label></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Assessment</button></div>
</form></div></div>

@if($errors->any())
<script>document.addEventListener('DOMContentLoaded',()=>document.querySelector('[data-modal-open="createAssessmentModal"]')?.click());</script>
@endif
@endsection
