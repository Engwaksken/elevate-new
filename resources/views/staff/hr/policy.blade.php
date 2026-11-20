@extends('layouts.admin')
@section('title',$policy->title.' | My HR')
@section('content')
@php $isPdf = $policy->file_name && str_ends_with(strtolower($policy->file_name), '.pdf'); @endphp
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow"><a href="{{ route('staff.hr.index') }}">My HR</a></span>
        <h1>{{ $policy->title }}</h1>
        <p>{{ collect([$policy->category, $policy->version ? 'Version '.$policy->version : null, $policy->effective_date ? 'Effective '.$policy->effective_date->format('d M Y') : null])->filter()->join(' · ') }}</p>
    </div>
    <div class="admin-page-actions">
        @if($policy->file_path)<a href="{{ route('staff.hr.policies.file', [$policy, 'download' => 1]) }}" class="btn btn-outline"><i class="fas fa-file-arrow-down" aria-hidden="true"></i> Download</a>@endif
    </div>
</div>

@if($policy->summary)<div class="admin-panel"><strong>Summary</strong><p style="margin:4px 0 0">{{ $policy->summary }}</p></div>@endif

@if($policy->file_path)
<div class="admin-panel">
    @if($isPdf)
        <iframe src="{{ route('staff.hr.policies.file', $policy) }}" title="{{ $policy->title }}" style="width:100%;height:75vh;border:1px solid var(--ad-border,#e3e5ea);border-radius:8px"></iframe>
    @else
        <p>This policy is a Word document. <a href="{{ route('staff.hr.policies.file', [$policy, 'download' => 1]) }}">Download {{ $policy->file_name }}</a> to read it.</p>
    @endif
</div>
@endif

@if($policy->body)
<div class="admin-panel"><div style="white-space:pre-line;max-width:80ch">{{ $policy->body }}</div></div>
@endif

@if($assignment && $policy->requires_acknowledgement)
<div class="admin-panel">
    @if($assignment->acknowledged_at)
        <div class="alert alert-success" role="status" style="margin:0">You acknowledged this policy on {{ $assignment->acknowledged_at->format('d M Y, H:i') }}.</div>
    @else
        <form method="POST" action="{{ route('staff.hr.policies.acknowledge', $policy) }}">@csrf
            <label style="display:flex;gap:10px;align-items:flex-start;font-weight:500"><input type="checkbox" name="confirm" value="1" required style="width:auto;margin-top:4px"> <span>I confirm that I have read and understood the <strong>{{ $policy->title }}</strong>{{ $policy->version ? ' (version '.$policy->version.')' : '' }} and agree to comply with it.</span></label>
            <button class="btn btn-primary" style="margin-top:10px">Acknowledge policy</button>
        </form>
    @endif
</div>
@endif
@endsection
