@extends('layouts.app')
@section('title','Review Cover Letter Upload - ElevateHer360')
@section('content')
<div class="career-page">
<div class="page-header">
    <div><span class="eyebrow">Cover Letter Import</span><h1>Review Uploaded Cover Letter</h1><p>Your current cover letter will not be overwritten automatically.</p></div>
    <a href="{{ route('career.resume.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Resume & Cover Letter Centre</a>
</div>

<div class="career-workspace">
    <div class="eh-tabs" data-eh-tabs>
        <div class="eh-tab-nav">
            <button class="eh-tab-button active" data-eh-tab="status"><i class="fas fa-circle-info"></i> Analysis</button>
            <button class="eh-tab-button" data-eh-tab="data"><i class="fas fa-list-check"></i> Extracted Data</button>
        </div>

        <div class="eh-tab-content">
            <section class="eh-tab-pane active" data-eh-pane="status">
                <div class="career-form-panel">
                    <div class="document-meta">
                        <div><span>File</span><strong>{{ $upload->original_name }}</strong></div>
                        <div><span>Status</span><strong>{{ ucfirst($upload->status) }}</strong></div>
                        <div><span>Uploaded</span><strong>{{ $upload->created_at->format('d M Y H:i') }}</strong></div>
                    </div>

                    @if(in_array($upload->status,['uploaded','processing']))
                        <div class="processing-status" data-upload-status-url="{{ route('career.cover-letter.upload.status',$upload) }}">
                            <span class="processing-spinner"></span>
                            <div><strong>Analysing your cover letter</strong><div>We are extracting your professional information. This page will refresh automatically when analysis finishes.</div></div>
                        </div>
                    @elseif($upload->status==='ready')
                        <div class="success-box"><i class="fas fa-circle-check"></i> Cover Letter analysis completed. Review the extracted information before importing.</div>
                    @elseif($upload->status==='failed')
                        <div class="error-box">{{ $upload->parsing_error ?: 'Cover Letter analysis failed.' }}</div>
                    @endif

                    <div class="form-actions">
                        <a href="{{ route('career.cover-letter.upload.original',$upload) }}" class="btn btn-outline"><i class="fas fa-file-arrow-down"></i> Original File</a>
                        @if($upload->status==='failed')
                            <form method="POST" action="{{ route('career.cover-letter.upload.retry',$upload) }}">@csrf<button class="btn btn-primary"><i class="fas fa-rotate"></i> Retry Analysis</button></form>
                        @endif
                    </div>
                </div>
            </section>

            <section class="eh-tab-pane" data-eh-pane="data">
                <div class="career-form-panel">
                    @if($upload->status==='ready')
                        <pre>{{ json_encode($upload->parsed_data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                        <div class="ai-note">Review the extracted information carefully. Importing creates a new structured cover letter; it does not overwrite an existing cover letter.</div>
                        <form method="POST" action="{{ route('career.cover-letter.upload.import',$upload) }}">@csrf
                            <button class="btn btn-primary"><i class="fas fa-file-import"></i> Import Into Cover Letter Builder</button>
                        </form>
                    @else
                        <div class="eh-empty"><p>Extracted information will appear when analysis is complete.</p></div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const node = document.querySelector('[data-upload-status-url]');
    if (!node) return;
    const timer = setInterval(async () => {
        try {
            const response = await fetch(node.dataset.uploadStatusUrl, {headers:{'Accept':'application/json'}});
            if (!response.ok) return;
            const result = await response.json();
            if (result.status === 'ready' || result.status === 'failed') {
                clearInterval(timer);
                window.location.reload();
            }
        } catch (_) {}
    }, 3000);
});
</script>
@endpush
@endsection
