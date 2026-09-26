@extends('layouts.app')
@section('title','Event Feedback | ElevateHer360')
@section('content')
<div class="page-header"><div><span class="eh-kicker">Event Evaluation</span><h1>{{ $event->title }}</h1><p>Share your experience to help us improve future events.</p></div></div>
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('events.feedback.store',$event) }}" class="eh-tab-section">@csrf
<div class="event-feedback-grid">
@foreach([
'overall_rating'=>'Overall experience',
'relevance_rating'=>'Relevance to your needs',
'facilitation_rating'=>'Facilitation quality',
'organisation_rating'=>'Organisation and logistics',
'recommend_rating'=>'Likelihood to recommend'
] as $field=>$label)
<div class="form-group"><label>{{ $label }} *</label><select name="{{ $field }}" required><option value="">Select 1–5</option>@for($i=1;$i<=5;$i++)<option value="{{ $i }}" @selected(old($field,$response->{$field})==$i)>{{ $i }} / 5</option>@endfor</select><small class="form-hint">1 = lowest, 5 = highest.</small></div>
@endforeach
<div class="form-group full"><label>Key Learning</label><textarea name="key_learning" rows="3" placeholder="What is the most important thing you learned?">{{ old('key_learning',$response->key_learning) }}</textarea></div>
<div class="form-group full"><label>What Worked Well?</label><textarea name="what_worked" rows="3" placeholder="What aspects of the event worked well?">{{ old('what_worked',$response->what_worked) }}</textarea></div>
<div class="form-group full"><label>What Should Improve?</label><textarea name="what_to_improve" rows="3" placeholder="What should we improve next time?">{{ old('what_to_improve',$response->what_to_improve) }}</textarea></div>
<div class="form-group full"><label>Additional Comments</label><textarea name="additional_comments" rows="3" placeholder="Add any other comments...">{{ old('additional_comments',$response->additional_comments) }}</textarea></div>
</div>
<div class="eh-form-actions"><button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Feedback</button></div>
</form>
@endsection
