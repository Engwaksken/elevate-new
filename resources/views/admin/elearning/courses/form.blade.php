@extends('layouts.app')
@section('content')
<div class="card">
<h1>{{ $course->exists ? 'Edit Course' : 'Add Course' }}</h1>
<form method="POST" action="{{ $course->exists ? route('admin.elearning.courses.update',$course) : route('admin.elearning.courses.store') }}">
@csrf @if($course->exists) @method('PUT') @endif
<label>Title</label><input name="title" value="{{ old('title',$course->title) }}" required>
<label>Code</label><input name="code" value="{{ old('code',$course->code) }}">
<label>Summary</label><textarea name="summary">{{ old('summary',$course->summary) }}</textarea>
<label>Description</label><textarea name="description" rows="6">{{ old('description',$course->description) }}</textarea>
<div class="grid">
<div><label>Programme</label><select name="programme_id"><option value="">None</option>@foreach($programmes as $p)<option value="{{ $p->id }}" @selected(old('programme_id',$course->programme_id)==$p->id)>{{ $p->name }}</option>@endforeach</select></div>
<div><label>Project</label><select name="project_id"><option value="">None</option>@foreach($projects as $p)<option value="{{ $p->id }}" @selected(old('project_id',$course->project_id)==$p->id)>{{ $p->name }}</option>@endforeach</select></div>
</div>
<div class="grid">
<div><label>Branch</label><select name="branch_id"><option value="">All/None</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id',$course->branch_id)==$b->id)>{{ $b->name }}</option>@endforeach</select></div>
<div><label>Delivery</label><select name="delivery_mode">@foreach(['online','in_person','blended'] as $d)<option value="{{ $d }}" @selected(old('delivery_mode',$course->delivery_mode)===$d)>{{ ucfirst(str_replace('_',' ',$d)) }}</option>@endforeach</select></div>
</div>
<div class="grid">
<div><label>Start date</label><input type="date" name="start_date" value="{{ old('start_date',optional($course->start_date)->format('Y-m-d')) }}"></div>
<div><label>End date</label><input type="date" name="end_date" value="{{ old('end_date',optional($course->end_date)->format('Y-m-d')) }}"></div>
</div>
<div class="grid">
<div><label>Duration hours</label><input type="number" name="duration_hours" value="{{ old('duration_hours',$course->duration_hours) }}"></div>
<div><label>Pass mark %</label><input type="number" step="0.01" name="pass_mark" value="{{ old('pass_mark',$course->pass_mark ?? 50) }}"></div>
</div>
<label><input style="width:auto" type="checkbox" name="self_enrolment_enabled" value="1" @checked(old('self_enrolment_enabled',$course->self_enrolment_enabled))> Allow self-enrolment</label>
<label>Status</label><select name="status">@foreach(['draft','published','archived'] as $s)<option value="{{ $s }}" @selected(old('status',$course->status)===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<button>Save Course</button>
</form>
</div>
@endsection
