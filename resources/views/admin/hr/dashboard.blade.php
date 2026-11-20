@extends('layouts.admin')
@section('title','HR Operations | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Human Resources</span><h1>HR Operations</h1><p>Recruitment, the employee directory, policies, staff documents, leave and performance in one place.</p></div>
    <div class="admin-page-actions">
        @if(auth()->user()->hasPermission('hr.manage'))
            <a href="{{ route('admin.hr.recruitment.index') }}#new" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> Post a job</a>
        @endif
    </div>
</div>
@include('admin.hr.partials.nav')

<div class="admin-stats-grid compact">
@foreach([
    ['employees','Employees','fa-users', route('admin.hr.employees.index')],
    ['open_vacancies','Open vacancies','fa-briefcase', route('admin.hr.recruitment.index', ['status' => 'open'])],
    ['new_applicants','New applicants','fa-inbox', route('admin.hr.recruitment.index')],
    ['shortlisted','Shortlisted / interview','fa-list-check', route('admin.hr.recruitment.index')],
    ['staff_outstanding','Staff with outstanding items','fa-triangle-exclamation', route('admin.hr.compliance.index', ['filter' => 'outstanding'])],
    ['documents_to_review','Documents to review','fa-file-circle-check', route('admin.hr.compliance.index').'#review'],
    ['policies','Active policies','fa-scale-balanced', route('admin.hr.policies.index')],
    ['leave_pending','Leave requests pending','fa-calendar-minus', route('admin.hr.leave.index')],
] as [$key,$label,$icon,$url])
<a class="admin-stat" href="{{ $url }}" style="text-decoration:none;color:inherit"><span class="admin-stat-icon"><i class="fas {{ $icon }}" aria-hidden="true"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></a>
@endforeach
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px">
    <section class="admin-panel" aria-labelledby="hr-int">
        <h2 id="hr-int" style="margin-top:0;font-size:1.05rem">Upcoming interviews</h2>
        @forelse($upcomingInterviews as $interview)
            <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--ad-border,#eee)">
                <div><strong>{{ $interview->applicant->name }}</strong><small class="admin-cell-hint">{{ $interview->vacancy->title }}</small></div>
                <div style="text-align:right"><span>{{ $interview->scheduled_at->format('D d M, H:i') }}</span><small class="admin-cell-hint">{{ \App\Models\RecruitmentInterview::MODES[$interview->mode] ?? $interview->mode }}</small></div>
            </div>
        @empty
            <div class="admin-empty">No interviews scheduled.</div>
        @endforelse
    </section>

    <section class="admin-panel" aria-labelledby="hr-vac">
        <h2 id="hr-vac" style="margin-top:0;font-size:1.05rem">Open vacancies</h2>
        @forelse($vacancies as $vacancy)
            <a href="{{ route('admin.hr.recruitment.show', $vacancy) }}" style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--ad-border,#eee);text-decoration:none;color:inherit">
                <div><strong>{{ $vacancy->title }}</strong><small class="admin-cell-hint">{{ $vacancy->closing_date ? 'Closes '.$vacancy->closing_date->format('d M Y') : 'No closing date' }}</small></div>
                <span>{{ $vacancy->applicants_count }} applicant(s)</span>
            </a>
        @empty
            <div class="admin-empty">No open vacancies. <a href="{{ route('admin.hr.recruitment.index') }}">Post one</a>.</div>
        @endforelse
    </section>

    <section class="admin-panel" aria-labelledby="hr-out">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
            <h2 id="hr-out" style="margin:0;font-size:1.05rem">Outstanding documents &amp; policies</h2>
            @if(auth()->user()->hasPermission('hr.manage') && $outstanding->isNotEmpty())
                <form method="POST" action="{{ route('admin.hr.compliance.remind') }}">@csrf<input type="hidden" name="scope" value="all"><button class="btn btn-outline btn-sm"><i class="fas fa-bell" aria-hidden="true"></i> Remind all</button></form>
            @endif
        </div>
        @forelse($outstanding as $row)
            <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--ad-border,#eee)">
                <strong>{{ $row['user']->name }}</strong>
                <span class="admin-cell-hint" style="text-align:right">{{ $row['documents'] }} document(s), {{ $row['policies'] }} polic{{ $row['policies'] === 1 ? 'y' : 'ies' }}</span>
            </div>
        @empty
            <div class="admin-empty">Every staff member is up to date.</div>
        @endforelse
    </section>
</div>
@endsection
