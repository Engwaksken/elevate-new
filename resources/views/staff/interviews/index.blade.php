@extends('layouts.admin')
@section('title','My Interview Panels | ElevateHer360')
@section('content')
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Recruitment</span><h1>My Interview Panels</h1><p>Interviews HR has invited you to. Open one to read the candidate's application and score each question.</p></div>
</div>
<div class="admin-panel">
<div class="admin-table-wrap"><table class="admin-table">
    <thead><tr><th>Candidate</th><th>Position</th><th>When</th><th>Mode</th><th>Your scores</th><th class="table-actions"></th></tr></thead>
    <tbody>
    @forelse($panels as $p)
        @php $i = $p->interview; @endphp
        <tr>
            <td><strong>{{ $i->applicant->name }}</strong>{{ $p->is_chair ? ' (you chair)' : '' }}</td>
            <td>{{ $i->vacancy->title }}</td>
            <td>{{ $i->scheduled_at->format('D d M Y, H:i') }}@if($i->status === 'cancelled')<small class="admin-cell-hint" style="color:#b42318">Cancelled</small>@endif</td>
            <td>{{ \App\Models\RecruitmentInterview::MODES[$i->mode] ?? $i->mode }}<small class="admin-cell-hint">{{ $i->location }}@if($i->meeting_link) <a href="{{ $i->meeting_link }}" target="_blank" rel="noopener noreferrer">Join link</a>@endif</small></td>
            <td>@if($p->submitted_at)<span class="status-chip active">Submitted {{ number_format((float) $p->total_score, 0) }}%</span>@elseif($i->status === 'cancelled')<span class="status-chip cancelled">-</span>@else<span class="status-chip draft">To do</span>@endif</td>
            <td class="table-actions"><a href="{{ route('staff.interviews.show', $i) }}" class="btn {{ $p->submitted_at ? 'btn-outline' : 'btn-primary' }} btn-sm">{{ $p->submitted_at ? 'View' : 'Score' }}</a></td>
        </tr>
    @empty
        <tr><td colspan="6"><div class="admin-empty">You have not been invited to any interview panels.</div></td></tr>
    @endforelse
    </tbody>
</table></div>
</div>
@endsection
