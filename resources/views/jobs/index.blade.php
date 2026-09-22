@extends('layouts.app')
@section('content')
<div class="card"><h1>Jobs</h1>
<form method="GET" class="grid">
<div><label>Search</label><input name="search" value="{{ request('search') }}"></div>
<div><label>Employment type</label><select name="employment_type"><option value="">All</option><option value="full_time">Full time</option><option value="part_time">Part time</option><option value="contract">Contract</option><option value="internship">Internship</option></select></div>
</form></div>
@foreach($jobs as $job)
<div class="card"><h3>{{ $job->title }}</h3><p>{{ $job->employer->company_name }} · {{ $job->location }}</p><a class="btn" href="{{ route('jobs.show',$job) }}">View Job</a></div>
@endforeach
{{ $jobs->links() }}
@endsection
