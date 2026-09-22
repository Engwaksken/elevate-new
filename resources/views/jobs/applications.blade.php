@extends('layouts.app')
@section('content')
<div class="card"><h1>My Applications</h1></div>
@foreach($applications as $application)
<div class="card"><strong>{{ $application->job->title }}</strong><br>{{ $application->job->employer->company_name }} · {{ $application->status }}</div>
@endforeach
{{ $applications->links() }}
@endsection
