@extends('layouts.app')
@section('content')
<div class="card"><h1>Job Approvals</h1>
@foreach($jobs as $job)
<div class="card"><strong>{{ $job->title }}</strong> · {{ $job->employer->company_name }} · {{ $job->status }}
@if($job->status==='pending_approval')
<form method="POST" action="{{ route('admin.jobs.publish',$job) }}" style="display:inline">@csrf<button>Publish</button></form>
<form method="POST" action="{{ route('admin.jobs.reject',$job) }}" style="display:inline">@csrf<button>Reject</button></form>
@endif
</div>
@endforeach
{{ $jobs->links() }}
</div>
@endsection
