@extends('layouts.admin')
@section('content')
<div class="card"><h1>{{ $assessment->title }}</h1>
<h3>Add Question</h3>
<form method="POST" action="{{ route('admin.elearning.questions.store',[$course,$assessment]) }}">@csrf
<label>Type</label><select name="question_type"><option value="multiple_choice">Multiple choice</option><option value="true_false">True/False</option><option value="short_text">Short text</option><option value="long_text">Long text</option></select>
<label>Question</label><textarea name="question_text" required></textarea>
<label>Options (one per line)</label><textarea name="options_text"></textarea>
<label>Correct value</label><input name="correct_value">
<label>Marks</label><input type="number" step="0.1" name="marks" value="1">
<button>Add Question</button>
</form></div>
@foreach($assessment->questions as $question)
<div class="card">{{ $loop->iteration }}. {{ $question->question_text }} ({{ $question->marks }} marks)
<form method="POST" action="{{ route('admin.elearning.questions.destroy',[$course,$assessment,$question]) }}">@csrf @method('DELETE')<button>Delete</button></form>
</div>
@endforeach
@endsection
