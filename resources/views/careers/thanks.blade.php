@extends('careers.layout')
@section('title', 'Application received')
@section('content')
<div class="c-card" role="status">
    <h1 style="margin:0 0 8px;font-size:1.5rem">Thank you for applying</h1>
    <p>We have received your application for <strong>{{ $vacancy->title }}</strong>.</p>
    @if($applicant)
        <p>Your reference number is <strong>{{ $applicant->reference }}</strong>. A confirmation has been sent to {{ $applicant->email }}.</p>
    @endif
    <p>Our HR team will review applications{{ $vacancy->closing_date ? ' after '.$vacancy->closing_date->format('d M Y') : '' }} and contact shortlisted candidates.</p>
    <a class="c-btn outline" href="{{ $embed ? route('careers.embed', ['host' => request('host')]) : route('careers.index') }}">See other positions</a>
</div>
@endsection
