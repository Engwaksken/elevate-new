@extends('layouts.admin')
@section('title','Learning Files | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Programme Delivery</span>
    <h1>Learning Files</h1>
    <p>Review and manage files uploaded to courses and lessons.</p>
</div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="admin-stats-grid compact">
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-folder-open"></i></span><div><small>Total Files</small><strong>{{ number_format($stats['total']??0) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-file-pdf"></i></span><div><small>PDF Files</small><strong>{{ number_format($stats['pdf']??0) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-photo-film"></i></span><div><small>Media Files</small><strong>{{ number_format($stats['media']??0) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-database"></i></span><div><small>Total Size</small><strong>{{ number_format(($stats['size']??0)/1048576,1) }} MB</strong></div></div>
</div>

<div class="admin-panel">
<form method="GET" action="{{ route('admin.elearning.learning-files.index') }}" class="admin-toolbar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search file name...">
    </div>

    <select name="course_id">
        <option value="">All courses</option>
        @foreach($courses as $course)
            <option value="{{ $course->id }}" @selected((string)request('course_id')===(string)$course->id)>
                {{ $course->title }}
            </option>
        @endforeach
    </select>

    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.elearning.learning-files.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>File</th>
    <th>Course</th>
    <th>Type</th>
    <th>Size</th>
    <th>Uploaded</th>
    <th class="table-actions">Actions</th>
</tr>
</thead>
<tbody>
@forelse($files as $file)
<tr>
    <td><strong>{{ $file->original_name }}</strong></td>
    <td>{{ $courses->firstWhere('id',$file->course_id)?->title ?: '—' }}</td>
    <td>{{ $file->mime_type ?: '—' }}</td>
    <td>{{ number_format(($file->size_bytes ?? 0)/1024,1) }} KB</td>
    <td>{{ optional($file->created_at)->format('d M Y') }}</td>
    <td class="table-actions">
        <div class="action-group">
            @if(Route::has('learning.files.download'))
                <a class="btn-icon" href="{{ route('learning.files.download',$file) }}" title="Download">
                    <i class="fas fa-download"></i>
                </a>
            @endif

            <button type="button" class="btn-icon danger" data-modal-open="deleteFile{{ $file->id }}" title="Delete">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty">No learning files found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $files->links() }}</div>
</div>

@foreach($files as $file)
<div class="eh-modal" id="deleteFile{{ $file->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-sm">
    <div class="eh-modal-header">
        <div>
            <h2>Delete Learning File?</h2>
            <p>{{ $file->original_name }}</p>
        </div>
        <button type="button" class="eh-modal-close" data-modal-close>
            <i class="fas fa-xmark"></i>
        </button>
    </div>

    <div class="eh-modal-body">
        <p>The stored file will also be deleted.</p>
    </div>

    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>

        <form method="POST" action="{{ route('admin.elearning.learning-files.destroy',$file) }}">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>
</div>
@endforeach
@endsection
