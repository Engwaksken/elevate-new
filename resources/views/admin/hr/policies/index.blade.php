@extends('layouts.admin')
@section('title','Policies | ElevateHer360 Administration')
@section('content')
@php $canManage = auth()->user()->hasPermission('hr.manage'); @endphp
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Human Resources</span><h1>Organisation Policies</h1><p>Publish policies, share them with staff (new staff get onboarding policies automatically) and track who has read and acknowledged them.</p></div>
    <div class="admin-page-actions">@if($canManage)<button type="button" class="btn btn-primary" data-modal-open="newPolicy"><i class="fas fa-plus" aria-hidden="true"></i> New policy</button>@endif</div>
</div>
@include('admin.hr.partials.nav')

<div class="admin-panel">
<div class="admin-table-wrap"><table class="admin-table">
    <thead><tr><th>Policy</th><th>Category</th><th>Version</th><th>Acknowledged</th><th>Onboarding</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
    <tbody>
    @forelse($policies as $policy)
        @php $pct = $policy->assignments_count ? round($policy->acknowledged_count / $policy->assignments_count * 100) : 0; @endphp
        <tr>
            <td><a href="{{ route('admin.hr.policies.show', $policy) }}"><strong>{{ $policy->title }}</strong></a>@if($policy->summary)<small class="admin-cell-hint">{{ \Illuminate\Support\Str::limit($policy->summary, 100) }}</small>@endif</td>
            <td>{{ $policy->category ?: '-' }}</td>
            <td>{{ $policy->version ?: '-' }}</td>
            <td>@if($policy->requires_acknowledgement){{ $policy->acknowledged_count }} / {{ $policy->assignments_count }} <small class="admin-cell-hint">{{ $pct }}%</small>@else<span class="admin-cell-hint">Read only · {{ $policy->assignments_count }} shared</span>@endif</td>
            <td>{{ $policy->assign_to_new_staff ? 'Yes' : 'No' }}</td>
            <td><span class="status-chip {{ $policy->is_active ? 'active' : 'inactive' }}">{{ $policy->is_active ? 'Active' : 'Archived' }}</span></td>
            <td class="table-actions"><a href="{{ route('admin.hr.policies.show', $policy) }}" class="btn btn-outline btn-sm">Manage</a></td>
        </tr>
    @empty
        <tr><td colspan="7"><div class="admin-empty">No policies yet. Add your code of conduct, safeguarding and HR policies so new staff receive them automatically.</div></td></tr>
    @endforelse
    </tbody>
</table></div>
</div>

@if($canManage)
<div class="eh-modal" id="newPolicy" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
    <div class="eh-modal-header"><div><h2>New policy</h2><p>Staff are notified in-app and by email when a policy is shared with them.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form method="POST" action="{{ route('admin.hr.policies.store') }}" enctype="multipart/form-data">@csrf
        <div class="eh-modal-body">
            @include('admin.hr.policies._fields', ['policy' => null])
            <hr style="margin:14px 0">
            @include('admin.hr.policies._share', ['prefix' => 'new', 'departments' => $departments, 'staff' => $staff])
        </div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Publish policy</button></div>
    </form>
</div></div>
@if($errors->any() && old('title'))<script>document.addEventListener('DOMContentLoaded',function(){var b=document.querySelector('[data-modal-open="newPolicy"]');if(b)b.click();});</script>@endif
@endif
@endsection
