@extends('layouts.admin')
@section('title','My HR | ElevateHer360')
@section('content')
@php
    $mine = $documents->groupBy('requirement_id');
    $pendingPolicies = $assignments->filter(fn ($a) => ! $a->acknowledged_at && $a->policy->requires_acknowledgement);
    $missingCount = $requirements->filter(fn ($r) => $r->is_mandatory && ! ($mine[$r->id] ?? collect())->whereIn('status', ['submitted', 'approved'])->count())->count();
@endphp
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Self-service</span><h1>My HR</h1><p>Read and acknowledge organisation policies, and upload the documents HR needs for your staff file.</p></div>
</div>

<div class="admin-stats-grid compact">
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-scale-balanced" aria-hidden="true"></i></span><div><small>Policies to acknowledge</small><strong>{{ $pendingPolicies->count() }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-file-circle-exclamation" aria-hidden="true"></i></span><div><small>Documents still needed</small><strong>{{ $missingCount }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-file-circle-check" aria-hidden="true"></i></span><div><small>Documents approved</small><strong>{{ $documents->where('status', 'approved')->count() }}</strong></div></div>
</div>

<section class="admin-panel" aria-labelledby="pol-h">
    <h2 id="pol-h" style="margin-top:0;font-size:1.05rem">Policies</h2>
    @forelse($assignments as $a)
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--ad-border,#eee);flex-wrap:wrap">
            <div>
                <a href="{{ route('staff.hr.policies.show', $a->policy) }}"><strong>{{ $a->policy->title }}</strong></a>
                <small class="admin-cell-hint">{{ collect([$a->policy->category, $a->policy->version ? 'v'.$a->policy->version : null])->filter()->join(' · ') }}@if(! $a->acknowledged_at && $a->due_date) · due {{ $a->due_date->format('d M Y') }}@if($a->due_date->isPast()) <strong style="color:#b42318">(overdue)</strong>@endif @endif</small>
            </div>
            @if($a->acknowledged_at)
                <span class="status-chip active">Acknowledged {{ $a->acknowledged_at->format('d M Y') }}</span>
            @elseif($a->policy->requires_acknowledgement)
                <a href="{{ route('staff.hr.policies.show', $a->policy) }}" class="btn btn-primary btn-sm">Read &amp; acknowledge</a>
            @else
                <a href="{{ route('staff.hr.policies.show', $a->policy) }}" class="btn btn-outline btn-sm">Read</a>
            @endif
        </div>
    @empty
        <div class="admin-empty">No policies have been shared with you yet.</div>
    @endforelse
</section>

<section class="admin-panel" aria-labelledby="doc-h">
    <h2 id="doc-h" style="margin-top:0;font-size:1.05rem">My documents</h2>
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>Document</th><th>Status</th><th>Your upload</th><th class="table-actions">Upload</th></tr></thead>
        <tbody>
        @forelse($requirements as $r)
            @php $latest = ($mine[$r->id] ?? collect())->first(); @endphp
            <tr>
                <td><strong>{{ $r->name }}</strong>{{ $r->is_mandatory ? ' *' : '' }}@if($r->description)<small class="admin-cell-hint">{{ $r->description }}</small>@endif</td>
                <td>
                    @if(! $latest)<span class="status-chip {{ $r->is_mandatory ? 'on_hold' : 'draft' }}">{{ $r->is_mandatory ? 'Required' : 'Optional' }}</span>
                    @elseif($latest->status === 'approved')<span class="status-chip active">Approved</span>
                    @elseif($latest->status === 'rejected')<span class="status-chip cancelled">Re-upload needed</span><small class="admin-cell-hint">{{ $latest->review_note }}</small>
                    @else<span class="status-chip draft">Awaiting HR review</span>@endif
                </td>
                <td>@if($latest)<a href="{{ route('staff.hr.documents.download', $latest) }}">{{ $latest->original_name }}</a><small class="admin-cell-hint">{{ $latest->created_at->format('d M Y') }}</small>@else - @endif</td>
                <td class="table-actions">
                    @if(! $latest || $latest->status === 'rejected' || $latest->status === 'submitted')
                    <form method="POST" action="{{ route('staff.hr.documents.store') }}" enctype="multipart/form-data" style="display:flex;gap:6px;justify-content:flex-end;align-items:center;flex-wrap:wrap">@csrf
                        <input type="hidden" name="requirement_id" value="{{ $r->id }}">
                        <input type="file" name="file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" aria-label="File for {{ $r->name }}" style="max-width:220px">
                        <button class="btn btn-primary btn-sm">{{ $latest ? 'Replace' : 'Upload' }}</button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="admin-empty">HR has not listed any required documents.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>

    <h3 style="font-size:.95rem;margin:16px 0 6px">Upload another document</h3>
    <form method="POST" action="{{ route('staff.hr.documents.store') }}" enctype="multipart/form-data" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">@csrf
        <div><label for="o-t" class="admin-cell-hint">Document name</label><input id="o-t" name="title" required placeholder="e.g. Professional certificate"></div>
        <div><label for="o-f" class="admin-cell-hint">File (PDF, Word or image, up to 10 MB)</label><input id="o-f" type="file" name="file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
        <button class="btn btn-outline btn-sm">Upload</button>
    </form>

    @php $others = $documents->whereNull('requirement_id'); @endphp
    @if($others->isNotEmpty())
        <ul style="margin:12px 0 0;padding-left:18px">
        @foreach($others as $doc)
            <li><a href="{{ route('staff.hr.documents.download', $doc) }}">{{ $doc->title }}</a> <span class="admin-cell-hint">· {{ ucfirst($doc->status) }}</span>
                @if($doc->status !== 'approved')<form method="POST" action="{{ route('staff.hr.documents.destroy', $doc) }}" style="display:inline" onsubmit="return confirm('Remove this document?')">@csrf @method('DELETE')<button class="btn btn-link btn-sm" style="padding:0 4px">Remove</button></form>@endif
            </li>
        @endforeach
        </ul>
    @endif
</section>
@endsection
