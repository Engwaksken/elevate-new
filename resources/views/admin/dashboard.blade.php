@extends('layouts.app')
@section('content')
<div class="card">
    <h1>ElevateHer360 Administration</h1>
    <p>Foundation dashboard for WITU staff.</p>
</div>
<div class="grid">
    <div class="card"><h3>Programmes</h3><a class="btn" href="{{ route('admin.programmes.index') }}">Manage</a></div>
    <div class="card"><h3>Projects</h3><a class="btn" href="{{ route('admin.projects.index') }}">Manage</a></div>
    <div class="card"><h3>Cohorts</h3><a class="btn" href="{{ route('admin.cohorts.index') }}">Manage</a></div>
    <div class="card"><h3>Branches</h3><a class="btn" href="{{ route('admin.branches.index') }}">Manage</a></div>
</div>
@endsection
