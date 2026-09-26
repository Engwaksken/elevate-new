@extends('layouts.admin')
@section('title','Survey Builder | ElevateHer360')
@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Survey Builder</span>
        <h1>{{$survey->title}}</h1>
        <p>{{$survey->description}}</p>
    </div>
    <div class="admin-page-actions">
        <a href="{{route('admin.surveys.responses',$survey)}}" class="btn btn-outline">Responses</a>
        <a href="{{route('admin.surveys.index')}}" class="btn btn-outline">Back</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $error)<div>{{$error}}</div>@endforeach</div>@endif

<div class="admin-panel">
    <h2>Add Section</h2>
    <form method="POST" action="{{route('admin.surveys.sections.store',$survey)}}" class="admin-toolbar">
        @csrf
        <input name="title" required placeholder="Section title">
        <input name="description" placeholder="Section description">
        <button class="btn btn-primary">Add Section</button>
    </form>
</div>

<div class="admin-panel">
    <h2>Add Question</h2>
    <form method="POST" action="{{route('admin.surveys.questions.store',$survey)}}">
        @csrf
        <div class="eh-form-grid">
            <div>
                <label>Section</label>
                <select name="survey_section_id">
                    <option value="">No section</option>
                    @foreach($survey->sections as $s)<option value="{{$s->id}}">{{$s->title}}</option>@endforeach
                </select>
            </div>
            <div>
                <label>Question Type</label>
                <select name="question_type">
                    @foreach($questionTypes as $t)<option value="{{$t}}">{{ucwords(str_replace('_',' ',$t))}}</option>@endforeach
                </select>
            </div>
            <div class="full">
                <label>Question</label>
                <textarea name="question_text" required placeholder="Enter question or instruction"></textarea>
            </div>
            <div class="full">
                <label>Hint</label>
                <input name="hint" placeholder="Question hint">
            </div>
            <div class="full">
                <label>Choices</label>
                <textarea name="options_text" placeholder="One choice per line"></textarea>
            </div>
            <div>
                <label>Show only when</label>
                <select name="condition_question_id">
                    <option value="">Always show</option>
                    @foreach($survey->questions as $existing)
                        <option value="{{$existing->id}}">{{Str::limit($existing->question_text,70)}}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Condition</label>
                <select name="condition_operator">
                    <option value="equals">Equals</option>
                    <option value="not_equals">Does not equal</option>
                    <option value="contains">Contains</option>
                    <option value="not_empty">Is not empty</option>
                </select>
            </div>
            <div>
                <label>Condition Value</label>
                <input name="condition_value" placeholder="Expected answer">
            </div>
            <div>
                <label><input type="checkbox" name="is_required" value="1"> Required</label>
            </div>
        </div>
        <button class="btn btn-primary">Add Question</button>
    </form>
</div>

@if($survey->access_type==='selected')
<div class="admin-panel">
    <h2>Selected Participants</h2>
    <form method="POST" action="{{route('admin.surveys.assignments',$survey)}}">
        @csrf @method('PUT')
        @php($assigned=$survey->assignments->pluck('user_id')->all())
        <div class="eh-participant-picker">
            @foreach($participants as $participant)
                <label class="eh-checkbox-row">
                    <input type="checkbox" name="participants[]" value="{{$participant->id}}" @checked(in_array($participant->id,$assigned))>
                    <span><strong>{{$participant->name}}</strong><small>{{$participant->email}}</small></span>
                </label>
            @endforeach
        </div>
        <button class="btn btn-primary">Save Participant Assignments</button>
    </form>
</div>
@endif

<div class="admin-panel">
    <div class="admin-panel-head">
        <div><h2>Questions</h2><p>Drag questions to change their order.</p></div>
        <span id="survey-order-status" class="admin-cell-hint"></span>
    </div>

    <div id="survey-sortable" data-reorder-url="{{route('admin.surveys.reorder',$survey)}}">
        @foreach($survey->questions()->orderBy('position')->get() as $q)
            <div class="survey-question-row eh-sortable-question" draggable="true" data-question-id="{{$q->id}}">
                <div class="eh-drag-handle"><i class="fas fa-grip-vertical"></i></div>
                <div class="eh-grow">
                    <strong>{{$q->question_text}}</strong>
                    <small class="admin-cell-hint">
                        {{ucwords(str_replace('_',' ',$q->question_type))}}
                        {{$q->is_required?' · Required':''}}
                        @if($q->conditional_logic)
                            · Conditional
                        @endif
                    </small>
                </div>
                <form method="POST" action="{{route('admin.surveys.questions.destroy',[$survey,$q])}}">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
