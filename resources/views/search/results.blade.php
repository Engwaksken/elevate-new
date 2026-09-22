@extends('layouts.app')
@section('content')
<div class="card"><h1>Search Results</h1><p>Query: {{ $term }}</p></div>
@foreach($results as $group=>$items)
<div class="card"><h2>{{ ucwords(str_replace('_',' ',$group)) }}</h2>
@if($items->isEmpty())<p>No matches.</p>@endif
@foreach($items as $item)
<div style="padding:8px 0;border-top:1px solid #eee">
{{ $item->name ?? $item->title ?? $item->asset_code ?? $item->request_number ?? ('#'.$item->id) }}
</div>
@endforeach
</div>
@endforeach
@endsection
