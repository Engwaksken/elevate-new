@extends('layouts.app')
@section('content')
<div class="card">
@if($certificate)
<h1>Certificate Verified</h1>
<p><strong>Certificate:</strong> {{ $certificate->certificate_number }}</p>
<p><strong>Learner:</strong> {{ $certificate->user->name }}</p>
<p><strong>Course:</strong> {{ $certificate->course->title }}</p>
<p><strong>Issued:</strong> {{ $certificate->issued_on->format('d M Y') }}</p>
@else
<h1>Certificate Not Found</h1>
@endif
</div>
@endsection
