@extends('layouts.admin')
@section('title','Course Opportunities | ElevateHer360')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Learning</span><h1>Course Opportunities</h1><p>Apply for open WITU course calls.</p></div></div>
<div class="eh-card-grid">@forelse($calls as $call)<a class="admin-panel eh-click-card" href="{{route('participant.course-calls.show',$call)}}"><span class="admin-eyebrow">{{$call->course?->title}}</span><h3>{{$call->title}}</h3><p>{{Str::limit(strip_tags($call->description),140)}}</p><small>Closes: {{$call->closes_at?->format('d M Y H:i') ?? 'Open until closed'}}</small><div><strong>{{isset($mine[$call->id]) ? ucwords($mine[$call->id]->status) : 'Apply'}}</strong></div></a>@empty<div class="admin-panel">No open course calls.</div>@endforelse</div>{{$calls->links()}}
@endsection