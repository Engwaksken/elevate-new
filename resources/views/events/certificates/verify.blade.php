@extends('layouts.app')
@section('title','Verify Event Certificate | ElevateHer360')
@section('content')
<div class="page-header"><div><span class="eh-kicker">Certificate Verification</span><h1>Valid Event Certificate</h1><p>This certificate was issued by ElevateHer360.</p></div></div>
<div class="eh-tab-section">
<div class="certificate-verify-grid">
<div><small>Participant</small><strong>{{ $certificate->user?->name }}</strong></div>
<div><small>Event</small><strong>{{ $certificate->event?->title }}</strong></div>
<div><small>Event Date</small><strong>{{ $certificate->event?->starts_at?->format('d M Y') }}</strong></div>
<div><small>Issued</small><strong>{{ $certificate->issued_at?->format('d M Y H:i') }}</strong></div>
<div class="full"><small>Certificate Code</small><strong>{{ $certificate->certificate_code }}</strong></div>
</div>
</div>
@endsection
