@extends('layouts.public')
@section('title',$survey->title.' | ElevateHer360')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Survey</span><h1>{{$survey->title}}</h1><p>{{$survey->description}}</p></div><a href="{{route('home')}}" class="btn btn-outline">Back</a></div>
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
<form method="POST" action="{{route('surveys.public.store',$survey)}}">@csrf 
@foreach($survey->questions as $q) @php($saved=null)
<div class="admin-panel"><label>{{$q->question_text}} {{$q->is_required?'*':''}}</label>@if($q->hint)<small class="admin-cell-hint">{{$q->hint}}</small>@endif
@if(in_array($q->question_type,['single_choice','dropdown','yes_no','rating','likert']))<select name="question_{{$q->id}}"><option value="">Select an answer</option>@foreach($q->question_type==='yes_no'?['Yes','No']:($q->options??[]) as $o)<option value="{{$o}}" @selected($saved?->answer_text===$o)>{{$o}}</option>@endforeach</select>
@elseif($q->question_type==='multiple_choice') @foreach($q->options??[] as $o)<label><input type="checkbox" name="question_{{$q->id}}[]" value="{{$o}}" @checked(in_array($o,$saved?->answer_json??[]))> {{$o}}</label>@endforeach
@elseif($q->question_type==='long_text')<textarea name="question_{{$q->id}}" placeholder="Your response">{{$saved?->answer_text}}</textarea>
@elseif(in_array($q->question_type,['heading','description']))<p>{{$q->hint}}</p>
@else<input type="{{in_array($q->question_type,['number','date','time','email'])?$q->question_type:'text'}}" name="question_{{$q->id}}" value="{{$saved?->answer_text}}" placeholder="Your response">@endif</div>@endforeach
<div class="admin-panel eh-form-actions"><button name="submit" value="1" class="btn btn-primary">Submit Survey</button></div></form>
@endsection