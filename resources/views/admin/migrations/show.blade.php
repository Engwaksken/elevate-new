@extends('layouts.app')
@section('content')
<div class="card"><h1>{{ $batch->batch_name }}</h1>
<p>{{ $batch->source_system }} · {{ $batch->total_rows }} records</p>
<table width="100%" cellpadding="6">
<tr><th align="left">Source ID</th><th>Email</th><th>Phone</th><th>Match</th><th>Matched User</th></tr>
@foreach($records as $record)
<tr>
<td>{{ $record->source_record_id }}</td>
<td>{{ data_get($record->source_payload,'email') }}</td>
<td>{{ data_get($record->source_payload,'phone') }}</td>
<td>{{ $record->match_status }}</td>
<td>{{ $record->matched_user_id }}</td>
</tr>
@endforeach
</table>
{{ $records->links() }}
</div>
@endsection
