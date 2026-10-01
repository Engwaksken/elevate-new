@extends('layouts.admin')
@section('title','Employment Contracts | ElevateHer360 Administration')
@section('content')
@php($canManage = auth()->user()?->hasPermission('hr.manage'))
@include('hr.contracts.partials.styles')

<div class="admin-page-header"><div><span class="admin-eyebrow">Human Resources</span><h1>Employment Contracts</h1><p>{{ data_get($employee,'user.name','Employee') }} · {{ $employee->employee_number }}</p></div><div class="admin-page-actions"><a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Employees</a> @if($canManage)<button type="button" class="btn btn-primary" data-modal-open="contract{{ $employee->id }}"><i class="fas fa-plus"></i> Add Contract</button>@endif</div></div>

<div class="admin-stats-grid compact">
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-file-contract"></i></span><div><small>Contracts</small><strong>{{ $contracts->count() }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-hourglass-half"></i></span><div><small>Awaiting Signature</small><strong>{{ $contracts->filter->isAwaitingSignature()->count() }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-signature"></i></span><div><small>Signed</small><strong>{{ $contracts->filter->isSigned()->count() }}</strong></div></div>
</div>

@forelse($contracts as $contract)
<div class="admin-panel ct-card">
<div class="admin-panel-head"><div><h2>{{ $contract->contract_type ?: 'Employment contract' }} <span class="status-chip {{ $contract->status }}">{{ ucfirst($contract->status) }}</span></h2><p>{{ optional($contract->start_date)->format('d M Y') }} – {{ optional($contract->end_date)->format('d M Y') ?: 'Open-ended' }}@if($contract->gross_salary !== null) · {{ $contract->currency }} {{ number_format((float)$contract->gross_salary,2) }}@endif</p></div><span class="status-chip {{ $contract->signatureChip() }}">{{ $contract->signatureLabel() }}</span></div>

<div class="ct-grid">
<div>
<dl class="ct-meta">
<dt>Contract file</dt><dd>
@if($contract->document_path)
{{ $contract->documentName() }}
<span class="ct-actions"><a href="{{ route('admin.hr.contracts.document',[$contract,'preview'=>1]) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener" data-file-preview data-file-preview-title="{{ $contract->documentName() }}"><i class="fas fa-eye"></i> Preview</a> <a href="{{ route('admin.hr.contracts.document',$contract) }}" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Download</a></span>
@else
<span class="ct-muted">No file attached</span>
@endif
</dd>
<dt>Sent for signature</dt><dd>{{ $contract->sent_for_signature_at?->format('d M Y H:i') ?: '—' }}@if($contract->sender) <span class="ct-muted">by {{ $contract->sender->name }}</span>@endif</dd>
@if($contract->isSigned())
<dt>Signed</dt><dd>{{ $contract->signed_at?->format('d M Y H:i:s') }} <span class="ct-muted">by {{ $contract->signer?->name ?: 'Employee' }}</span></dd>
<dt>Method</dt><dd>{{ $contract->signature_method === 'drawn' ? 'Drawn on screen' : 'Uploaded signature image' }}@if($contract->signer_ip) <span class="ct-muted">· IP {{ $contract->signer_ip }}</span>@endif</dd>
@if($contract->signer_comment)<dt>Employee comment</dt><dd>{{ $contract->signer_comment }}</dd>@endif
<dt>Signature certificate</dt><dd><span class="ct-actions"><a href="{{ route('admin.hr.contracts.certificate',[$contract,'preview'=>1]) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener" data-file-preview data-file-preview-title="Signature certificate · {{ $contract->contract_type ?: 'Contract' }}"><i class="fas fa-eye"></i> Preview</a> <a href="{{ route('admin.hr.contracts.certificate',$contract) }}" class="btn btn-outline btn-sm"><i class="fas fa-file-pdf"></i> Download PDF</a></span></dd>
@endif
</dl>

@if($canManage && ! $contract->isSigned())
<div class="ct-actions ct-manage">
<button type="button" class="btn btn-outline btn-sm" data-modal-open="contractFile{{ $contract->id }}"><i class="fas fa-upload"></i> {{ $contract->document_path ? 'Replace file' : 'Attach file' }}</button>
@if($contract->document_path)
<form method="POST" action="{{ route('admin.hr.contracts.send',$contract) }}">@csrf<button class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> {{ $contract->isAwaitingSignature() ? 'Resend reminder' : 'Send for signature' }}</button></form>
@endif
</div>
@endif
</div>

<div class="ct-signature-box">
@if($contract->isSigned())
<img src="{{ route('admin.hr.contracts.signature',$contract) }}" alt="Signature of {{ $contract->signer?->name }}">
<small>Signed {{ $contract->signed_at?->format('d M Y H:i') }}</small>
@else
<span class="ct-muted"><i class="fas fa-signature"></i> {{ $contract->isAwaitingSignature() ? 'Waiting for the employee to sign' : 'Not yet signed' }}</span>
@endif
</div>
</div>
</div>

@if($canManage && ! $contract->isSigned())
<div class="eh-modal" id="contractFile{{ $contract->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>{{ $contract->document_path ? 'Replace Contract File' : 'Attach Contract File' }}</h2><p>{{ $contract->contract_type ?: 'Employment contract' }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.hr.contracts.document.update',$contract) }}" enctype="multipart/form-data">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Contract File *</label><input type="file" name="document" required accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"><small class="form-hint">PDF, DOC or DOCX, up to 10 MB. Files can only be changed until the employee signs.</small></div>
<div class="form-group full"><label><input type="checkbox" name="send_for_signature" value="1" checked> Send to the employee for signature</label></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Upload</button></div>
</form></div></div>
@endif
@empty
<div class="admin-panel"><div class="admin-empty">No contracts recorded for this employee yet.</div></div>
@endforelse

@if($canManage)
@include('admin.hr.employees.partials.contract-modal',['employee'=>$employee])
@endif
@endsection
