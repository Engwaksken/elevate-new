@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','My Certificates - ElevateHer360')
@section('content')
<div class="page-header">
    <div>
        <span class="eh-kicker">Achievements</span>
        <h1>My Certificates</h1>
        <p>Preview, download and share certificates issued to you.</p>
    </div>
</div>

<div class="eh-data-list">
@foreach($certificates as $certificate)
<div class="eh-data-row">
    <div class="eh-data-row-main">
        <span class="eh-data-row-icon"><i class="fas fa-certificate"></i></span>
        <div class="eh-data-row-copy">
            <strong>{{ $certificate->course?->title ?? $certificate->event?->title ?? 'Certificate' }}</strong>
            <span>Course certificate · {{ $certificate->issued_on?->format('d M Y') }} · {{ $certificate->certificate_number }}</span>
        </div>
    </div>
    <div class="eh-data-row-actions">
    <a class="btn btn-outline btn-sm" href="{{ route('certificates.file.preview',['type'=>'course','id'=>$certificate->id]) }}" data-file-preview data-file-preview-title="{{ $certificate->course?->title ?? 'Certificate' }}">Preview</a>
    <a class="btn btn-outline btn-sm" href="{{ route('certificates.download',$certificate) }}">
        <i class="fas fa-file-pdf"></i> Download PDF
    </a>
    <button type="button" class="btn btn-outline btn-sm" data-certificate-share="{{ route('certificates.file.share',['type'=>'course','id'=>$certificate->id]) }}">Share</button>
    </div>
</div>
@endforeach

@if($certificates->onFirstPage())
@foreach($eventCertificates as $certificate)
@continue(! $certificate->event)
<div class="eh-data-row">
    <div class="eh-data-row-main">
        <span class="eh-data-row-icon"><i class="fas fa-award"></i></span>
        <div class="eh-data-row-copy">
            <strong>{{ $certificate->event->title }}</strong>
            <span>Event certificate · {{ $certificate->issued_at?->format('d M Y') }}</span>
        </div>
    </div>
    <div class="eh-data-row-actions">
    <a class="btn btn-outline btn-sm" href="{{ route('certificates.file.preview',['type'=>'event','id'=>$certificate->id]) }}" data-file-preview data-file-preview-title="{{ $certificate->event->title }}">Preview</a>
    <a class="btn btn-outline btn-sm" href="{{ route('certificates.file.download',['type'=>'event','id'=>$certificate->id]) }}">
        <i class="fas fa-file-pdf"></i> Download PDF
    </a>
    <button type="button" class="btn btn-outline btn-sm" data-certificate-share="{{ route('certificates.file.share',['type'=>'event','id'=>$certificate->id]) }}">Share</button>
    </div>
</div>
@endforeach
@endif

@if($certificates->isEmpty() && $eventCertificates->isEmpty())
<div class="eh-empty">
    <i class="fas fa-certificate"></i>
    <h3>No certificates yet</h3>
    <p>Your certificates will appear here once issued.</p>
</div>
@endif
</div>

{{ $certificates->links() }}
@push('scripts')
<script>
document.querySelectorAll('[data-certificate-share]').forEach(button => button.addEventListener('click', async () => {
    button.disabled = true;
    try {
        const response = await fetch(button.dataset.certificateShare, {method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':@json(csrf_token())}});
        if (!response.ok) throw new Error('Unable to share this certificate. Please retry.');
        const data = await response.json();
        if (navigator.share) {
            try { await navigator.share({title:'Certificate', text:'Certificate link expires in seven days.', url:data.url}); }
            catch (error) { if (error.name !== 'AbortError') window.prompt('Copy certificate link (expires in seven days):', data.url); }
        } else window.prompt('Copy certificate link (expires in seven days):', data.url);
    } catch (error) { window.alert(error.message); }
    finally { button.disabled = false; }
}));
</script>
@endpush
@endsection
