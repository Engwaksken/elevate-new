@extends('layouts.app')
@section('content')
<div class="card"><h1>Employers</h1>
@foreach($employers as $employer)
<div class="card"><strong>{{ $employer->company_name }}</strong> · {{ $employer->status }}
@if($employer->status==='pending')
<form method="POST" action="{{ route('admin.jobs.employers.approve',$employer) }}" style="display:inline">@csrf<button>Approve</button></form>
<form method="POST" action="{{ route('admin.jobs.employers.reject',$employer) }}" style="display:inline">@csrf<button>Reject</button></form>
@endif
</div>
@endforeach
{{ $employers->links() }}
</div>
@endsection
