@extends('layouts.admin')
@section('title','Survey Responses | ElevateHer360')
@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Survey Responses</span>
        <h1>{{$survey->title}}</h1>
        <p>Monitor completion and review participant responses.</p>
    </div>
    <div class="admin-page-actions">
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('survey_responses.export'))
            <a href="{{route('admin.surveys.responses.csv',$survey)}}" class="btn btn-outline">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
        @endif
        <a href="{{route('admin.surveys.builder',$survey)}}" class="btn btn-outline">Builder</a>
    </div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Responses','fa-clipboard-list'],
['submitted','Submitted','fa-circle-check'],
['draft','In Progress','fa-pen'],
['unique_participants','Participants','fa-users']
] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{$icon}}"></i></span><div><small>{{$label}}</small><strong>{{number_format($stats[$key]??0)}}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Respondent</th><th>Status</th><th>Submitted</th><th>Answers</th></tr></thead><tbody>
@forelse($responses as $r)
<tr>
<td>{{$r->user?->name ?? 'Anonymous'}}<small class="admin-cell-hint">{{$r->user?->email}}</small></td>
<td>{{ucfirst($r->status)}}</td>
<td>{{$r->submitted_at?->format('d M Y H:i') ?? '—'}}</td>
<td>
@foreach($r->answers as $a)
<div class="eh-response-answer"><strong>{{$a->question?->question_text}}</strong><span>{{$a->answer_text ?? implode(', ',$a->answer_json??[])}}</span></div>
@endforeach
</td>
</tr>
@empty<tr><td colspan="4">No responses.</td></tr>@endforelse
</tbody></table></div>
{{$responses->links()}}
</div>
@endsection
