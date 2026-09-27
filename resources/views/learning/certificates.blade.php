@extends('layouts.app')
@section('title','My Certificates - ElevateHer360')
@section('content')
<div class="page-header">
    <div>
        <span class="eh-kicker">Achievements</span>
        <h1>My Certificates</h1>
        <p>View and download certificates issued to you.</p>
    </div>
</div>

<div class="eh-data-list">
@forelse($certificates as $certificate)
<div class="eh-data-row">
    <div class="eh-data-row-main">
        <span class="eh-data-row-icon"><i class="fas fa-certificate"></i></span>
        <div class="eh-data-row-copy">
            <strong>{{ $certificate->course?->title ?? $certificate->event?->title ?? 'Certificate' }}</strong>
            <span>{{ $certificate->issued_on?->format('d M Y') }} Â· {{ $certificate->certificate_number }}</span>
        </div>
    </div>
    <a class="btn btn-outline btn-sm" href="{{ route('certificates.download',$certificate) }}">
        <i class="fas fa-file-pdf"></i> Download PDF
    </a>
</div>
@empty
<div class="eh-empty">
    <i class="fas fa-certificate"></i>
    <h3>No certificates yet</h3>
    <p>Your certificates will appear here once issued.</p>
</div>
@endforelse
</div>

{{ $certificates->links() }}
@endsection