@extends('layouts.admin')
@section('title','Recruitment | ElevateHer360 Administration')
@section('content')
@php $canManage = auth()->user()->hasPermission('hr.manage'); @endphp
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Human Resources</span><h1>Recruitment</h1><p>Post jobs on the careers page or embed them on any website, let AI rank applicants against your criteria, then shortlist and interview.</p></div>
    <div class="admin-page-actions">
        <a href="{{ route('careers.index') }}" target="_blank" rel="noopener" class="btn btn-outline"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> Careers page</a>
        @if($canManage)<button type="button" class="btn btn-primary" data-modal-open="newVacancyModal" id="new"><i class="fas fa-plus" aria-hidden="true"></i> Post a job</button>@endif
    </div>
</div>
@include('admin.hr.partials.nav')

<div class="admin-stats-grid compact">
@foreach([['open','Open vacancies','fa-briefcase'],['applicants','Total applicants','fa-users'],['new','Awaiting screening','fa-inbox'],['shortlisted','Shortlisted / interview','fa-list-check']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}" aria-hidden="true"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar" data-auto-filter>
    <div class="search-box"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><input name="search" value="{{ request('search') }}" placeholder="Search title, reference or location..." aria-label="Search vacancies"></div>
    <select name="status" aria-label="Status"><option value="">All statuses</option>@foreach(\App\Models\RecruitmentVacancy::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
    <button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.hr.recruitment.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>
<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Vacancy</th><th>Department</th><th>Closing</th><th>Applicants</th><th>New</th><th>Shortlisted</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($vacancies as $vacancy)
<tr>
    <td><a href="{{ route('admin.hr.recruitment.show', $vacancy) }}"><strong>{{ $vacancy->title }}</strong></a><small class="admin-cell-hint">{{ collect([$vacancy->reference, $vacancy->location, $vacancy->employment_type])->filter()->join(' · ') }}</small></td>
    <td>{{ $vacancy->department->name ?? '-' }}</td>
    <td>{{ optional($vacancy->closing_date)->format('d M Y') ?: '-' }}</td>
    <td>{{ $vacancy->applicants_count }}</td>
    <td>{{ $vacancy->new_count }}</td>
    <td>{{ $vacancy->shortlisted_count }}</td>
    <td><span class="status-chip {{ $vacancy->status }}">{{ ucfirst($vacancy->status) }}</span></td>
    <td class="table-actions"><a href="{{ route('admin.hr.recruitment.show', $vacancy) }}" class="btn btn-outline btn-sm">Open</a></td>
</tr>
@empty
<tr><td colspan="8"><div class="admin-empty">No vacancies yet. @if($canManage)Use <strong>Post a job</strong> to create your first one.@endif</div></td></tr>
@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $vacancies->links() }}</div>
</div>

@if($canManage)
<div class="eh-modal" id="newVacancyModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
    <div class="eh-modal-header"><div><h2>Post a job</h2><p>Save as Draft to prepare it, or Open to publish on the careers page and embeds.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form method="POST" action="{{ route('admin.hr.recruitment.store') }}">@csrf
        <div class="eh-modal-body">@include('admin.hr.recruitment._form', ['vacancy' => null])</div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save vacancy</button></div>
    </form>
</div></div>
@if($errors->any() && old('title'))
<script>document.addEventListener('DOMContentLoaded',function(){var b=document.querySelector('[data-modal-open="newVacancyModal"]');if(b)b.click();});</script>
@endif
<script>if(location.hash==='#new'){document.addEventListener('DOMContentLoaded',function(){var b=document.querySelector('[data-modal-open="newVacancyModal"]');if(b)b.click();});}</script>
@endif
@endsection
