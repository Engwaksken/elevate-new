@extends('layouts.app')
@section('content')
<div class="card"><h1>Deliverables</h1></div>
@foreach($deliverables as $deliverable)
<div class="card"><strong>{{ $deliverable->title }}</strong><br>{{ $deliverable->status }} · {{ $deliverable->progress_percent }}%</div>
@endforeach
{{ $deliverables->links() }}
@endsection
