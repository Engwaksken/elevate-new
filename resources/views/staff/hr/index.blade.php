@extends('layouts.admin')
@section('title','My HR | ElevateHer360')
@section('content')
@php
    $mine = $documents->groupBy('requirement_id');
    $pendingPolicies = $assignments->filter(fn ($a) => ! $a->acknowledged_at && $a->policy->requires_acknowledgement);
    $missingCount = $requirements->filter(fn ($r) => $r->is_mandatory && ! ($mine[$r->id] ?? collect())->whereIn('status', ['submitted', 'approved'])->count())->count();
    $others = $documents->whereNull('requirement_id');
    $uploadErrors = $errors->hasAny(['title', 'file']) && old('_form') === 'other';
    $tab = request('tab', session('tab'));
    if (! in_array($tab, ['policies', 'documents'], true)) {
        $tab = $pendingPolicies->isEmpty() && $missingCount > 0 ? 'documents' : 'policies';
    }
    if ($uploadErrors) $tab = 'documents';
    $tabs = [
        'policies' => ['Policies', 'fa-scale-balanced', $pendingPolicies->count()],
        'documents' => ['My documents', 'fa-folder-open', $missingCount],
    ];
@endphp
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Self-service</span><h1>My HR</h1><p>Read and acknowledge organisation policies, and upload the documents HR needs for your staff file.</p></div>
    <div class="admin-page-actions">
        <button type="button" class="btn btn-primary" data-modal-open="uploadOtherModal"><i class="fas fa-upload" aria-hidden="true"></i> Upload another document</button>
    </div>
</div>

<div class="admin-stats-grid compact">
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-scale-balanced" aria-hidden="true"></i></span><div><small>Policies to acknowledge</small><strong>{{ $pendingPolicies->count() }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-file-circle-exclamation" aria-hidden="true"></i></span><div><small>Documents still needed</small><strong>{{ $missingCount }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-file-circle-check" aria-hidden="true"></i></span><div><small>Documents approved</small><strong>{{ $documents->where('status', 'approved')->count() }}</strong></div></div>
</div>

<div class="admin-panel" data-hr-tabs>
    <div class="admin-tab-nav" role="tablist" aria-label="My HR sections">
        @foreach($tabs as $key => [$label, $icon, $count])
            <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="pane-{{ $key }}" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" tabindex="{{ $tab === $key ? '0' : '-1' }}"
                class="admin-tab-button {{ $tab === $key ? 'active' : '' }}" data-hr-tab="{{ $key }}" style="display:inline-flex;align-items:center;gap:6px">
                <i class="fas {{ $icon }}" aria-hidden="true"></i> {{ $label }}
                @if($count)<span class="ag-count" style="background:#b42318;color:#fff">{{ $count }}</span>@endif
            </button>
        @endforeach
    </div>

    {{-- ============ POLICIES ============ --}}
    <div class="admin-tab-pane {{ $tab === 'policies' ? 'active' : '' }}" role="tabpanel" id="pane-policies" aria-labelledby="tab-policies" tabindex="0" style="padding-top:14px">
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
    </div>

    {{-- ============ MY DOCUMENTS ============ --}}
    <div class="admin-tab-pane {{ $tab === 'documents' ? 'active' : '' }}" role="tabpanel" id="pane-documents" aria-labelledby="tab-documents" tabindex="0" style="padding-top:14px">
        <h2 style="font-size:1rem;margin:0 0 8px">Required by HR</h2>
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
                        @if(! $latest || $latest->status !== 'approved')
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

        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin:18px 0 8px;flex-wrap:wrap">
            <h2 style="font-size:1rem;margin:0">Other documents</h2>
            <button type="button" class="btn btn-outline btn-sm" data-modal-open="uploadOtherModal"><i class="fas fa-upload" aria-hidden="true"></i> Upload another document</button>
        </div>
        @if($others->isEmpty())
            <div class="admin-empty">You have not uploaded any other documents.</div>
        @else
            <div class="admin-table-wrap"><table class="admin-table">
                <thead><tr><th>Document</th><th>File</th><th>Status</th><th class="table-actions"></th></tr></thead>
                <tbody>
                @foreach($others as $doc)
                    <tr>
                        <td><strong>{{ $doc->title }}</strong><small class="admin-cell-hint">{{ $doc->created_at->format('d M Y') }}</small></td>
                        <td><a href="{{ route('staff.hr.documents.download', $doc) }}">{{ $doc->original_name }}</a></td>
                        <td><span class="status-chip {{ ['approved' => 'active', 'rejected' => 'cancelled'][$doc->status] ?? 'draft' }}">{{ $doc->status === 'submitted' ? 'Awaiting HR review' : ucfirst($doc->status) }}</span></td>
                        <td class="table-actions">
                            @if($doc->status !== 'approved')
                                <form method="POST" action="{{ route('staff.hr.documents.destroy', $doc) }}" onsubmit="return confirm('Remove this document?')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm">Remove</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </div>
</div>

<div class="eh-modal" id="uploadOtherModal" aria-hidden="true" @if($uploadErrors) data-modal-autoopen @endif><div class="eh-modal-dialog">
    <div class="eh-modal-header"><div><h2>Upload another document</h2><p>Anything else for your staff file, e.g. a professional certificate or reference letter. HR will review it.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form method="POST" action="{{ route('staff.hr.documents.store') }}" enctype="multipart/form-data">@csrf
        <input type="hidden" name="_form" value="other">
        <div class="eh-modal-body"><div class="modal-grid">
            <div class="form-group" style="grid-column:1/-1"><label for="o-t">Document name *</label><input id="o-t" name="title" value="{{ $uploadErrors ? old('title') : '' }}" required maxlength="190" placeholder="e.g. Professional certificate">@if($uploadErrors)@error('title')<small class="form-error" style="color:#b42318">{{ $message }}</small>@enderror @endif</div>
            <div class="form-group" style="grid-column:1/-1"><label for="o-f">File *</label><input id="o-f" type="file" name="file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"><small class="form-hint">PDF, Word or image, up to 10 MB.</small>@if($uploadErrors)@error('file')<small class="form-error" style="color:#b42318;display:block">{{ $message }}</small>@enderror @endif</div>
        </div></div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Upload</button></div>
    </form>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('[data-hr-tabs]');
    if (!root) return;
    var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-hr-tab]'));
    function select(key, focus) {
        tabs.forEach(function (t) {
            var on = t.dataset.hrTab === key;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            document.getElementById('pane-' + t.dataset.hrTab).classList.toggle('active', on);
            if (on && focus) t.focus();
        });
        try { var u = new URL(location.href); u.searchParams.set('tab', key); history.replaceState(null, '', u); } catch (e) {}
    }
    tabs.forEach(function (t, i) {
        t.addEventListener('click', function () { select(t.dataset.hrTab); });
        t.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            var next = tabs[(i + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
            select(next.dataset.hrTab, true);
        });
    });
});
</script>
@endsection
