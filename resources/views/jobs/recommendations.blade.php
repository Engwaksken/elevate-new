@extends('layouts.app')
@section('content')
<div class="card"><h1>Jobs Recommended for You</h1></div>
@foreach($jobs as $job)
<div class="card"><strong>{{ $job->title }}</strong><br>{{ $job->employer->company_name }} · Match {{ $job->recommendation_score }}%</div>
@endforeach
@endsection
