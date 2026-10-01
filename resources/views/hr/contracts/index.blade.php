@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','My Contracts | ElevateHer360')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">People &amp; Performance</span><h1>My Contracts</h1><p>Review the employment contracts HR has shared with you and sign them electronically.</p></div></div>

<div class="admin-panel">
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Contract</th><th>Period</th><th>Shared</th><th>Signature</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($contracts as $contract)
<tr>
<td><strong>{{ $contract->contract_type ?: 'Employment contract' }}</strong><small class="admin-cell-hint">{{ $contract->documentName() }}</small></td>
<td>{{ optional($contract->start_date)->format('d M Y') }} – {{ optional($contract->end_date)->format('d M Y') ?: 'Open-ended' }}</td>
<td>{{ $contract->sent_for_signature_at?->format('d M Y') ?: '—' }}</td>
<td><span class="status-chip {{ $contract->signatureChip() }}">{{ $contract->signatureLabel() }}</span>@if($contract->isSigned())<small class="admin-cell-hint">{{ $contract->signed_at?->format('d M Y H:i') }}</small>@endif</td>
<td class="table-actions"><a href="{{ route('staff.contracts.show',$contract) }}" class="btn {{ $contract->isAwaitingSignature() ? 'btn-primary' : 'btn-outline' }} btn-sm"><i class="fas {{ $contract->isAwaitingSignature() ? 'fa-file-signature' : 'fa-eye' }}"></i> {{ $contract->isAwaitingSignature() ? 'Review & Sign' : 'View' }}</a></td>
</tr>
@empty
<tr><td colspan="5"><div class="admin-empty">No contracts have been shared with you yet.</div></td></tr>
@endforelse
</tbody></table></div>
</div>
@endsection
