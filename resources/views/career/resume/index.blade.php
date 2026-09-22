@extends('layouts.app')
@section('content')
<div class="card"><h1>My Resumes</h1><a class="btn" href="{{ route('career.resume.create') }}">Create Resume</a></div>
@foreach($resumes as $resume)
<div class="card"><strong>{{ $resume->title }}</strong> · {{ $resume->template }} @if($resume->is_default) · Default @endif
<br><a href="{{ route('career.resume.edit',$resume) }}">Edit</a>
<form method="POST" action="{{ route('career.resume.default',$resume) }}" style="display:inline">@csrf<button>Make Default</button></form>
<a class="btn" href="{{ route('career.resume.download',$resume) }}">Download PDF</a>
</div>
@endforeach
@endsection
