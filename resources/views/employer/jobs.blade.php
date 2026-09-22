@extends('layouts.app')
@section('content')
<div class="card"><h1>Employer Jobs</h1>
@if($employer->status==='approved')
<form method="POST" action="{{ route('employer.jobs.store') }}">@csrf
<label>Job title</label><input name="title" required>
<label>Industry</label><input name="industry">
<label>Location</label><input name="location">
<label>Country</label><input name="country">
<label>Description</label><textarea name="description"></textarea>
<label>Requirements</label><textarea name="requirements"></textarea>
<label>Skills (comma separated)</label><input name="skills_text">
<label>Deadline</label><input type="date" name="application_deadline">
<label>Positions</label><input type="number" name="positions" value="1">
<button>Submit Job</button>
</form>
@else
<p>Your employer profile must be approved before posting jobs.</p>
@endif
</div>
@foreach($jobs as $job)<div class="card">{{ $job->title }} · {{ $job->status }}</div>@endforeach
{{ $jobs->links() }}
@endsection
