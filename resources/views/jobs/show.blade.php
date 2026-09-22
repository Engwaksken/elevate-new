@extends('layouts.app')
@section('content')
<div class="card"><h1>{{ $job->title }}</h1>
<p><strong>{{ $job->employer->company_name }}</strong></p>
<p>{{ $job->description }}</p>
<p><strong>Requirements</strong></p><p>{{ $job->requirements }}</p>
@if(auth()->check())
<form method="POST" action="{{ route('jobs.apply',$job) }}">@csrf
<label>Cover letter</label><textarea name="cover_letter"></textarea>
<button>Apply</button>
</form>
@endif
</div>
@endsection
