@extends('layouts.app')
@section('content')
<div class="card"><h1>Applicants</h1>
@foreach($applications as $application)
<div class="card"><strong>{{ $application->user->name }}</strong><br>{{ $application->job->title }} · {{ $application->status }}
<form method="POST" action="{{ route('employer.applicants.status',$application) }}">@csrf @method('PUT')
<select name="status"><option value="under_review">Under Review</option><option value="shortlisted">Shortlisted</option><option value="interview">Interview</option><option value="offer">Offer</option><option value="hired">Hired</option><option value="rejected">Rejected</option></select>
<input name="notes" placeholder="Notes">
<button>Update</button></form>
</div>
@endforeach
{{ $applications->links() }}
</div>
@endsection
