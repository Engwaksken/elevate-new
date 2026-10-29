@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','My Certificates - ElevateHer360')
@section('content')
<div class="eh-page-hero">
    <div>
        <span class="eh-kicker">Achievements</span>
        <h1>My Certificates</h1>
        <p>Preview, download and share certificates issued to you.</p>
    </div>
    <div class="page-actions"><x-export-buttons /></div>
</div>

<div class="admin-panel">
<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Certificate</th><th>Type</th><th>Issued</th><th>Certificate No.</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@foreach($certificates as $certificate)
<tr>
    <td><strong>{{ $certificate->course?->title ?? $certificate->event?->title ?? 'Certificate' }}</strong></td>
    <td><span class="status-chip active"><i class="fas fa-certificate"></i> Course</span></td>
    <td>{{ $certificate->issued_on?->format('d M Y') ?? '-' }}</td>
    <td>{{ $certificate->certificate_number ?: '-' }}</td>
    <td class="table-actions"><div class="action-group">
        <a class="btn btn-outline btn-sm" href="{{ route('certificates.file.preview',['type'=>'course','id'=>$certificate->id]) }}" data-file-preview data-file-preview-title="{{ $certificate->course?->title ?? 'Certificate' }}"><i class="fas fa-eye"></i> Preview</a>
        <a class="btn btn-outline btn-sm" href="{{ route('certificates.download',$certificate) }}"><i class="fas fa-file-pdf"></i> Download PDF</a>
        <button type="button" class="btn btn-outline btn-sm" data-certificate-share="{{ route('certificates.file.share',['type'=>'course','id'=>$certificate->id]) }}"><i class="fas fa-share-nodes"></i> Share</button>
    </div></td>
</tr>
@endforeach

@if($certificates->onFirstPage())
@foreach($eventCertificates as $certificate)
@continue(! $certificate->event)
<tr>
    <td><strong>{{ $certificate->event->title }}</strong></td>
    <td><span class="status-chip pending"><i class="fas fa-award"></i> Event</span></td>
    <td>{{ $certificate->issued_at?->format('d M Y') ?? '-' }}</td>
    <td>-</td>
    <td class="table-actions"><div class="action-group">
        <a class="btn btn-outline btn-sm" href="{{ route('certificates.file.preview',['type'=>'event','id'=>$certificate->id]) }}" data-file-preview data-file-preview-title="{{ $certificate->event->title }}"><i class="fas fa-eye"></i> Preview</a>
        <a class="btn btn-outline btn-sm" href="{{ route('certificates.file.download',['type'=>'event','id'=>$certificate->id]) }}"><i class="fas fa-file-pdf"></i> Download PDF</a>
        <button type="button" class="btn btn-outline btn-sm" data-certificate-share="{{ route('certificates.file.share',['type'=>'event','id'=>$certificate->id]) }}"><i class="fas fa-share-nodes"></i> Share</button>
    </div></td>
</tr>
@endforeach
@endif

@if($certificates->isEmpty() && $eventCertificates->isEmpty())
<tr><td colspan="5"><div class="admin-empty"><i class="fas fa-certificate"></i><strong>No certificates yet</strong><span>Your certificates will appear here once issued.</span></div></td></tr>
@endif
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $certificates->links() }}</div>
</div>
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
