@extends('layouts.app')
@section('content')
<div class="card"><h1>Unified Calendar</h1></div>
@foreach($events as $event)
<div class="card"><strong>{{ $event->title }}</strong><br>{{ $event->event_type }} · {{ $event->starts_at->format('d M Y H:i') }} @if($event->ends_at) - {{ $event->ends_at->format('d M Y H:i') }} @endif</div>
@endforeach
{{ $events->links() }}
@endsection
