@extends('layouts.app')
@section('title', 'Course Feedback | ElevateHer360')
@section('content')
<div class="page-header"><div><span class="eh-kicker">Course Evaluation</span><h1>{{ $course->title }}</h1><p>Tell us what worked well and what could be improved.</p></div></div>
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('courses.feedback.store', $course) }}" class="eh-tab-section">
    @csrf
    <div class="event-feedback-grid">
        @foreach(['overall_rating'=>'Overall experience','content_rating'=>'Course content','instructor_rating'=>'Instructor / facilitation','recommend_rating'=>'Recommend this course'] as $field=>$label)
            <div class="form-group"><label for="{{ $field }}">{{ $label }} (1 = poor, 5 = excellent) *</label>
                <select id="{{ $field }}" name="{{ $field }}" required>
                    <option value="">Choose a rating</option>
                    @for($rating=1;$rating<=5;$rating++)<option value="{{ $rating }}" @selected((string)old($field,$response->$field)===(string)$rating)>{{ $rating }}</option>@endfor
                </select>
            </div>
        @endforeach
        <div class="form-group"><label for="key_learning">What was your most important learning?</label><textarea id="key_learning" name="key_learning" maxlength="5000">{{ old('key_learning',$response->key_learning) }}</textarea></div>
        <div class="form-group"><label for="what_to_improve">What could be improved?</label><textarea id="what_to_improve" name="what_to_improve" maxlength="5000">{{ old('what_to_improve',$response->what_to_improve) }}</textarea></div>
        <div class="form-group"><label for="additional_comments">Additional comments</label><textarea id="additional_comments" name="additional_comments" maxlength="5000">{{ old('additional_comments',$response->additional_comments) }}</textarea></div>
    </div>
    <button class="btn btn-primary" type="submit">Submit course feedback</button>
    <a class="btn btn-outline" href="{{ route('participant.feedback.index') }}">Back to feedback</a>
</form>
@endsection
