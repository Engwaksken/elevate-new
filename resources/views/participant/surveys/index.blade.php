@extends('layouts.admin')
@section('title','Surveys | ElevateHer360')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">M&E</span><h1>My Surveys</h1><p>Complete surveys assigned to you.</p></div></div><div class="eh-card-grid">@forelse($surveys as $survey)<a class="admin-panel eh-click-card" href="{{route('participant.surveys.show',$survey)}}"><span class="admin-eyebrow">{{ucwords($survey->access_type)}}</span><h3>{{$survey->title}}</h3><p>{{Str::limit($survey->description,140)}}</p><strong>Open Survey</strong></a>@empty<div class="admin-panel">No surveys are currently assigned to you.</div>@endforelse</div>{{$surveys->links()}}
@endsection