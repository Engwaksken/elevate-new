{{-- Opens the issued certificate, or a draft preview of a recommended one. Expects $recommendation. --}}
@php($issued = $recommendation->certificate_id || $recommendation->event_certificate_id)
<a class="btn {{ $issued ? 'btn-primary' : 'btn-outline' }} btn-sm" href="{{ route('certificates.staff.recommendation', $recommendation) }}" target="_blank" rel="noopener"
   title="{{ $issued ? 'Open the issued certificate' : 'Preview the certificate that would be issued. Nothing is saved.' }}">
    <i class="fas {{ $issued ? 'fa-file-pdf' : 'fa-file-circle-question' }}"></i> {{ $issued ? 'View certificate' : 'Preview certificate' }}
</a>
