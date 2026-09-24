@extends('layouts.admin')
@section('content')
<div class="card"><h1>Bulk Enrolment</h1>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.elearning.bulk-enrolment.store') }}">@csrf
<label>Course</label><select name="course_id">@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select>
<label>CSV</label><input type="file" name="file" accept=".csv,.txt" required>
<p>CSV must contain an <strong>email</strong> column.</p>
<button>Enrol Learners</button>
</form></div>
@endsection
