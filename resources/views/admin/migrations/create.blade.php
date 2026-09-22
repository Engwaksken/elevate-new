@extends('layouts.app')
@section('content')
<div class="card"><h1>Stage Legacy Migration</h1>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.migrations.store') }}">
@csrf
<label>Source system</label><select name="source_system" required><option value="elearning">eLearning</option><option value="mentorship">Mentorship</option><option value="jobs">Jobs</option><option value="library">Library</option><option value="other">Other</option></select>
<label>Batch name</label><input name="batch_name" required>
<label>CSV file</label><input type="file" name="payload" accept=".csv,.txt" required>
<button>Stage File</button>
</form>
</div>
@endsection
