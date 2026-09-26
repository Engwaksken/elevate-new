@extends('layouts.admin')
@section('title',$assessment->title.' | Assessment Builder')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Assessment Builder</span><h1>{{ $assessment->title }}</h1><p>{{ ucfirst($assessment->type) }} · Pass mark {{ number_format((float)$assessment->pass_mark,0) }}% · {{ $assessment->questions->count() }} question(s)</p></div>
<div class="admin-page-actions"><a href="{{ route('admin.elearning.assessments.index',$course) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Assessments</a><button type="button" class="btn btn-primary" data-modal-open="addQuestionModal"><i class="fas fa-plus"></i> Add Question</button></div>
</div>

<div class="admin-panel">
<div class="assessment-question-list">
@forelse($assessment->questions as $question)
<article class="assessment-question-card">
<div class="assessment-question-head">
<div><strong>{{ $question->position ?: $loop->iteration }}. {{ $question->question_text }}</strong><small>{{ ucfirst(str_replace('_',' ',$question->question_type)) }} · {{ number_format((float)$question->marks,1) }} mark(s)</small></div>
<button type="button" class="btn-icon danger" data-modal-open="deleteQuestion{{ $question->id }}" title="Delete question"><i class="fas fa-trash"></i></button>
</div>
@if(is_array($question->options) && count($question->options))
<div class="assessment-options">@foreach($question->options as $key=>$value)<div><span>{{ $key }}</span>{{ $value }}</div>@endforeach</div>
@endif
@if(data_get($question->correct_answer,'value')!==null)<small class="assessment-answer">Correct value: {{ data_get($question->correct_answer,'value') }}</small>@endif
</article>
@empty<div class="admin-empty">No questions yet. Add the first question to this assessment.</div>@endforelse
</div>
</div>

<div class="eh-modal" id="addQuestionModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>Add Question</h2><p>{{ $assessment->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.elearning.questions.store',[$course,$assessment]) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Question Type *</label><select name="question_type">@foreach(['multiple_choice'=>'Multiple choice','true_false'=>'True / False','short_text'=>'Short text','long_text'=>'Long text'] as $v=>$l)<option value="{{ $v }}" @selected(old('question_type')===$v)>{{ $l }}</option>@endforeach</select></div>
<div class="form-group"><label>Marks *</label><input type="number" step=".1" min=".1" name="marks" value="{{ old('marks',1) }}" required></div>
<div class="form-group"><label>Position</label><input type="number" min="1" name="position" value="{{ old('position',$assessment->questions->count()+1) }}"></div>
<div class="form-group full"><label>Question *</label><textarea name="question_text" required rows="4">{{ old('question_text') }}</textarea></div>
<div class="form-group full"><label>Options</label><textarea name="options_text" rows="5" placeholder="One option per line for multiple-choice questions">{{ old('options_text') }}</textarea></div>
<div class="form-group full"><label>Correct Value</label><input name="correct_value" value="{{ old('correct_value') }}"><small class="form-hint">For multiple choice, use the zero-based option key (0, 1, 2...). For True/False use true or false.</small></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Add Question</button></div>
</form></div></div>

@foreach($assessment->questions as $question)
<div class="eh-modal" id="deleteQuestion{{ $question->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Delete Question?</h2><p>{{ Str::limit($question->question_text,80) }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>This permanently removes the question from the assessment.</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.elearning.questions.destroy',[$course,$assessment,$question]) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete Question</button></form></div>
</div></div>
@endforeach

@if($errors->any())<script>document.addEventListener('DOMContentLoaded',()=>document.querySelector('[data-modal-open="addQuestionModal"]')?.click());</script>@endif
@endsection
