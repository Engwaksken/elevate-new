@extends('layouts.app')
@section('content')
<div class="card"><h1>Cohorts</h1>
<p><a class="btn" href="{{ route('admin.cohorts.create') }}">Add Cohort</a></p>
<p>Controller includes search and pagination. Replace this starter table with the approved ElevateHer360 admin UI during the design pass.</p>
@foreach($cohorts as $cohort)<div class="card">{{ $cohort->name }} <a href="{{ route('admin.cohorts.edit',$cohort) }}">Edit</a></div>@endforeach
{{ $cohorts->links() }}</div>
@endsection
