@extends('layouts.app')
@section('content')
<div class="card"><h1>{{ $branch->exists ? 'Edit' : 'Add' }} Branch</h1>
<p>This starter view intentionally leaves module-specific fields to the controller schema. Build the polished form UI from the approved field list in the PM specification.</p>
</div>
@endsection
