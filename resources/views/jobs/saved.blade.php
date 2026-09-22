@extends('layouts.app')
@section('content')
<div class="card"><h1>Saved Jobs</h1></div>
@foreach($jobs as $job)
<div class="card"><strong>{{ $job->title }}</strong><br>{{ $job->employer->company_name }}</div>
@endforeach
{{ $jobs->links() }}
@endsection
