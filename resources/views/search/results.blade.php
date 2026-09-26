@extends('layouts.admin')
@section('title','Global Search | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div>
<span class="admin-eyebrow">Administration</span>
<h1>Global Search</h1>
<p>Search across people, learning, jobs, library, workplans, activities, assets and procurement.</p>
</div>
</div>

<div class="admin-panel">
<form method="GET" action="{{ route('admin.search') }}" class="admin-toolbar global-search-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="q" value="{{ $term }}" placeholder="Search name, email, course, job, asset, workplan..."></div>
<select name="category">
@foreach(['all'=>'All areas','users'=>'Users','courses'=>'Courses','jobs'=>'Jobs','library'=>'Library','workplans'=>'Workplans','activities'=>'Activities','assets'=>'Assets','purchase_requests'=>'Purchase Requests'] as $value=>$label)
<option value="{{ $value }}" @selected($category===$value)>{{ $label }}</option>
@endforeach
</select>
<button class="btn btn-primary">Search</button>
</form>
</div>

@if($term==='')
<div class="admin-empty"><i class="fas fa-search"></i><h3>Search ElevateHer360</h3><p>Enter a keyword above.</p></div>
@else
@php($total=collect($results)->sum(fn($collection)=>$collection->count()))
<div class="admin-stats-grid compact">
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-search"></i></span><div><small>Total Matches</small><strong>{{ number_format($total) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-layer-group"></i></span><div><small>Areas Matched</small><strong>{{ collect($results)->filter(fn($v)=>$v->isNotEmpty())->count() }}</strong></div></div>
</div>

@forelse($results as $type=>$items)
@if($items->isNotEmpty())
<section class="admin-panel">
<div class="admin-panel-head"><div><h2>{{ ucwords(str_replace('_',' ',$type)) }}</h2><p>{{ $items->count() }} match(es)</p></div></div>
<div class="global-result-grid">
@foreach($items as $item)
<article class="global-result-card">
<strong>{{ $item->name ?? $item->title ?? $item->asset_code ?? $item->request_number ?? 'Record' }}</strong>
<small>
{{ $item->email ?? $item->code ?? $item->author ?? $item->asset_tag ?? $item->industry ?? '' }}
</small>
</article>
@endforeach
</div>
</section>
@endif
@empty
@endforelse

@if($total===0)
<div class="admin-empty"><i class="fas fa-magnifying-glass"></i><h3>No results</h3><p>No records matched “{{ $term }}”.</p></div>
@endif
@endif
@endsection
