@extends('layouts.admin')
@section('content')
<div class="card"><h1>Migration Batches</h1>
<p><a class="btn" href="{{ route('admin.migrations.create') }}">New Migration Batch</a></p>
@foreach($batches as $batch)
<div class="card">
<strong>{{ $batch->batch_name }}</strong><br>
Source: {{ $batch->source_system }} | Rows: {{ $batch->total_rows }} | Status: {{ $batch->status }}
<br><a href="{{ route('admin.migrations.show',$batch) }}">Review</a>
</div>
@endforeach
{{ $batches->links() }}
</div>
@endsection
