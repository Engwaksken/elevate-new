@extends('layouts.admin')
@section('title','Certificate Templates | ElevateHer360')
@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Learning</span>
        <h1>Certificate Templates</h1>
        <p>Upload templates for a specific course, event or the default certificate design.</p>
    </div>
</div>

<div class="admin-panel">
<form method="POST" action="{{ route('admin.elearning.certificates.templates.store') }}" enctype="multipart/form-data">
@csrf
<div class="modal-grid">
    <div class="form-group"><label>Template Name</label><input name="name" required></div>

    <div class="form-group">
        <label>Use For</label>
        <select name="context_type" required>
            <option value="course">Specific Course</option>
            <option value="event">Specific Event</option>
            <option value="default">Default</option>
        </select>
    </div>

    <div class="form-group">
        <label>Course</label>
        <select name="course_id">
            <option value="">Select course</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}">{{ $course->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Event</label>
        <select name="event_id">
            <option value="">Select event</option>
            @foreach($events as $event)
                <option value="{{ $event->id }}">{{ $event->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Orientation</label>
        <select name="orientation">
            <option value="landscape">Landscape</option>
            <option value="portrait">Portrait</option>
        </select>
    </div>

    <div class="form-group">
        <label>Template Image</label>
        <input type="file" name="background" accept=".png,.jpg,.jpeg,.webp" required>
    </div>
</div>

<button class="btn btn-primary">
    <i class="fas fa-upload"></i> Upload Template
</button>
</form>
</div>

<div class="admin-panel">
<div class="eh-data-list">
@forelse($templates as $template)
<div class="eh-data-row">
    <div class="eh-data-row-main">
        <span class="eh-data-row-icon"><i class="fas fa-certificate"></i></span>
        <div class="eh-data-row-copy">
            <strong>{{ $template->name }}</strong>
            <span>
                {{ ucfirst($template->context_type) }}
                @if($template->course) Â· {{ $template->course->title }} @endif
                @if($template->event) Â· {{ $template->event->title }} @endif
            </span>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.elearning.certificates.templates.destroy',$template) }}">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i> Delete</button>
    </form>
</div>
@empty
<div class="eh-empty">No certificate templates uploaded.</div>
@endforelse
</div>

{{ $templates->links() }}
</div>
@endsection