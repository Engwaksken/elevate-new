<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Performance Appraisal - {{ $appraisal->employee?->user?->name }}</title>
<style>
body{font-family:Arial,sans-serif;color:#222;margin:28px;font-size:12px}
h1{font-size:20px;margin:0 0 4px}h2{font-size:15px;margin:18px 0 8px}
.meta{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:14px 0}
.meta div,.summary div{border:1px solid #ccc;padding:8px}
.summary{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin:14px 0}
table{width:100%;border-collapse:collapse;margin-bottom:14px}
th,td{border:1px solid #bbb;padding:6px;vertical-align:top}
th{background:#eee}
.kra{background:#f6f6f6;font-weight:bold}
.small{font-size:10px;color:#555}
@media print{.no-print{display:none}body{margin:8mm}}
</style>
</head>
<body>
<div class="no-print" style="margin-bottom:14px"><button onclick="window.print()">Print / Save PDF</button></div>

<h1>WITU Performance Appraisal</h1>
<div>{{ $appraisal->cycle?->name }}</div>

<div class="meta">
<div><strong>Employee</strong><br>{{ $appraisal->employee?->user?->name }}</div>
<div><strong>Manager</strong><br>{{ $appraisal->manager?->name ?: '—' }}</div>
<div><strong>Status</strong><br>{{ ucwords(str_replace('_',' ',$appraisal->status)) }}</div>
</div>

<div class="summary">
<div><strong>Workflow Completion</strong><br>{{ number_format((float)$appraisal->completion_percent,1) }}%</div>
<div><strong>Performance</strong><br>{{ $appraisal->performance_percent !== null ? number_format((float)$appraisal->performance_percent,1).'%' : '—' }}</div>
</div>

<table>
<thead><tr><th>KRA / KPI</th><th>Employee Rating</th><th>Manager Rating</th><th>Agreed Rating</th><th>Comments</th></tr></thead>
<tbody>
@foreach($items as $item)
@if($item->item_type==='kra')
<tr class="kra"><td colspan="5">{{ $item->title }} @if($item->weight) ({{ number_format((float)$item->weight,0) }}%) @endif</td></tr>
@elseif(!($item->item_type==='behavioral' && data_get($item->meta,'group')))
@php($score=$scores->get($item->id))
<tr>
<td><strong>{{ $item->title }}</strong><div class="small">{{ $item->section }}</div></td>
<td>{{ $item->item_type==='okr' ? ($score?->okr_percent !== null ? number_format((float)$score->okr_percent,1).'%' : '—') : ($score?->employee_rating ?? '—') }}</td>
<td>{{ $score?->manager_rating ?? '—' }}</td>
<td>{{ $score?->agreed_rating ?? '—' }}</td>
<td>
@if($score?->employee_comment)<strong>Employee:</strong> {{ $score->employee_comment }}<br>@endif
@if($score?->manager_comment)<strong>Manager:</strong> {{ $score->manager_comment }}@endif
</td>
</tr>
@endif
@endforeach
</tbody>
</table>

<h2>Overall Comments</h2>
<p><strong>Employee:</strong> {{ $appraisal->employee_comments ?: '—' }}</p>
<p><strong>Manager:</strong> {{ $appraisal->manager_comments ?: '—' }}</p>
<p><strong>Development Plan:</strong> {{ $appraisal->development_plan ?: '—' }}</p>

<h2>Acknowledgements</h2>
<p>Employee: {{ $appraisal->employee_acknowledgement_name ?: '—' }} {{ $appraisal->employee_acknowledged_at ? '('.$appraisal->employee_acknowledged_at->format('d M Y H:i').')' : '' }}</p>
<p>Manager: {{ $appraisal->manager_acknowledgement_name ?: '—' }} {{ $appraisal->manager_acknowledged_at ? '('.$appraisal->manager_acknowledged_at->format('d M Y H:i').')' : '' }}</p>
<p>HR: {{ $appraisal->finalisedBy?->name ?: '—' }} {{ $appraisal->hr_finalised_at ? '('.$appraisal->hr_finalised_at->format('d M Y H:i').')' : '' }}</p>
</body>
</html>
