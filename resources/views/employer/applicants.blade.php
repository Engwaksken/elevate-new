@extends('layouts.app')
@section('content')
<div class="card"><div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><h1>Applicants</h1><x-export-buttons /></div>
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
