@extends('layouts.admin')
@section('title', 'Learning Reports | ElevateHer360 Administration')

@section('content')
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Reports</span><h1>Learning Report</h1><p>Review enrolments, completion and learning progress by course.</p></div>
    @if(auth()->user()->hasPermission('reports.view'))
    <div class="admin-page-actions"><a class="btn btn-outline" href="{{ $csvUrl }}"><i class="fas fa-file-csv"></i> Export CSV</a><a class="btn btn-primary" href="{{ $pdfUrl }}"><i class="fas fa-file-pdf"></i> Export PDF</a></div>
    @endif
</div>

@if(auth()->user()->hasPermission('reports.view'))
<div class="admin-panel">
    <form method="GET" action="{{ route('admin.learning-reports.index') }}" class="admin-toolbar eh-filter-row">
        <select name="course_id"><option value="">All courses</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string)($filters['course_id'] ?? '') === (string)$course->id)>{{ $course->title }}</option>@endforeach</select>
        <select name="programme_id"><option value="">All programmes</option>@foreach($programmes as $programme)<option value="{{ $programme->id }}" @selected((string)($filters['programme_id'] ?? '') === (string)$programme->id)>{{ $programme->name }}</option>@endforeach</select>
        <select name="branch_id"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string)($filters['branch_id'] ?? '') === (string)$branch->id)>{{ $branch->name }}</option>@endforeach</select>
        <label>Enrolled from <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
        <label>Enrolled to <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
        <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-filter"></i> Apply</button>
        <a class="btn btn-outline btn-sm" href="{{ route('admin.learning-reports.index') }}">Reset</a>
    </form>
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>Course</th><th>Programme</th><th>Branch</th><th>Enrolments</th><th>Completed</th><th>In progress</th><th>Completion rate</th><th>Average progress</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr><td><strong>{{ $row['course'] }}</strong></td><td>{{ $row['programme_id'] ?: '—' }}</td><td>{{ $row['branch_id'] ?: '—' }}</td><td>{{ number_format($row['enrolments']) }}</td><td>{{ number_format($row['completed']) }}</td><td>{{ number_format($row['in_progress']) }}</td><td>{{ number_format((float)$row['completion_rate_percent'], 2) }}%</td><td>{{ number_format((float)$row['average_progress_percent'], 2) }}%</td></tr>
        @empty
            <tr><td colspan="8"><div class="admin-empty">No learning records match these filters.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endif
@endsection
