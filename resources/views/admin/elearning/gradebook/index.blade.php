@extends('layouts.admin')
@section('title','Gradebook | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Programme Delivery</span><h1>Gradebook — {{ $course->title }}</h1><p>Review assessment attempts and grade submitted learner work.</p></div>
<div class="admin-page-actions"><a href="{{ route('admin.elearning.assessments.index',$course) }}" class="btn btn-outline"><i class="fas fa-clipboard-question"></i> Assessments</a></div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Attempts','fa-list-check'],
['submitted','Awaiting Grading','fa-clock'],
['graded','Graded','fa-circle-check'],
['average','Average %','fa-chart-line']
] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ is_numeric($stats[$key] ?? null) ? number_format((float)$stats[$key],$key==='average'?1:0) : ($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search learner or assessment..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['in_progress','submitted','graded'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select>
<select name="per_page">@foreach([10,20,30,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',30)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.elearning.gradebook.index',$course) }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Learner</th><th>Assessment</th><th>Attempt</th><th>Submitted</th><th>Score</th><th>Percentage</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($attempts as $attempt)
<tr>
<td><strong>{{ data_get($attempt,'user.name','—') }}</strong><small class="admin-cell-hint">{{ data_get($attempt,'user.email','') }}</small></td>
<td>{{ data_get($attempt,'assessment.title','—') }}</td>
<td>#{{ $attempt->attempt_number }}</td>
<td>{{ optional($attempt->submitted_at)->format('d M Y H:i') ?: '—' }}</td>
<td>{{ $attempt->score !== null ? number_format((float)$attempt->score,1) : '—' }}</td>
<td>{{ $attempt->percentage !== null ? number_format((float)$attempt->percentage,1).'%' : '—' }}</td>
<td><span class="status-chip {{ $attempt->status }}">{{ ucfirst(str_replace('_',' ',$attempt->status)) }}</span></td>
<td class="table-actions"><a class="btn btn-outline btn-sm" href="{{ route('admin.elearning.gradebook.edit',$attempt) }}"><i class="fas fa-pen-to-square"></i> Grade</a></td>
</tr>
@empty<tr><td colspan="8"><div class="admin-empty">No assessment attempts found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $attempts->links() }}</div>
</div>
@endsection
