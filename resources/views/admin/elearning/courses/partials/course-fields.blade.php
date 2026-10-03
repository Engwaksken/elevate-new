@php
$editing = isset($course) && $course;
$formTabsConfig = [
    'overview' => ['label' => 'Overview', 'icon' => 'fa-book-open', 'fields' => ['title', 'code', 'summary', 'description', 'thumbnail', 'remove_thumbnail']],
    'organisation' => ['label' => 'Organisation', 'icon' => 'fa-sitemap', 'fields' => ['delivery_mode', 'programme_id', 'project_id', 'branch_ids']],
    'schedule' => ['label' => 'Schedule & Publishing', 'icon' => 'fa-calendar-days', 'fields' => ['start_date', 'end_date', 'duration_hours', 'pass_mark', 'status', 'self_enrolment_enabled', 'entry_assessment_id']],
];
@endphp
<x-form-tabs :id="$editing ? 'course-edit-'.$course->id : 'course-create'" label="Course details" :tabs="$formTabsConfig">
<x-form-tab name="overview">
<div class="modal-grid">
    <div class="form-group full"><label>Title *</label><input name="title" value="{{ old('title',$editing ? $course->title : '') }}" required></div>
    <div class="form-group"><label>Course Code</label><input name="code" value="{{ old('code',$editing ? $course->code : '') }}"></div>
    <div class="form-group full"><label>Summary</label><textarea name="summary" rows="3">{{ old('summary',$editing ? $course->summary : '') }}</textarea></div>
    <div class="form-group full"><label>Description</label><textarea name="description" rows="6">{{ old('description',$editing ? $course->description : '') }}</textarea></div>
    <div class="form-group full"><label>Course image (JPG, PNG or WEBP, up to 4 MB)</label><input type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp">@if($editing && $course->thumbnail_path)<img src="{{ $course->thumbnail_url }}" alt="Current course image" style="max-width:200px;max-height:120px;object-fit:cover"><label class="modal-check"><input type="checkbox" name="remove_thumbnail" value="1"><span>Remove current image</span></label>@endif</div>
</div>
</x-form-tab>
<x-form-tab name="organisation">
<div class="modal-grid">
    <div class="form-group"><label>Delivery Mode *</label><select name="delivery_mode" required>@foreach(['online'=>'Online','in_person'=>'In person','blended'=>'Blended'] as $v=>$l)<option value="{{ $v }}" @selected(old('delivery_mode',$editing ? $course->delivery_mode : 'online')===$v)>{{ $l }}</option>@endforeach</select></div>
    <div class="form-group"><label>Programme</label><select name="programme_id"><option value="">None</option>@foreach($programmes as $x)<option value="{{ $x->id }}" @selected((string)old('programme_id',$editing ? $course->programme_id : '')===(string)$x->id)>{{ $x->name }}</option>@endforeach</select></div>
    <div class="form-group"><label>Project</label><select name="project_id"><option value="">None</option>@foreach($projects as $x)<option value="{{ $x->id }}" @selected((string)old('project_id',$editing ? $course->project_id : '')===(string)$x->id)>{{ $x->name }}</option>@endforeach</select></div>
    <div class="form-group full"><label>Branches (select all that apply)</label>
        <input type="hidden" name="sync_branches" value="1">
        @foreach($branches as $x)
            <label class="modal-check"><input type="checkbox" name="branch_ids[]" value="{{ $x->id }}" @checked(in_array($x->id, old('branch_ids', $editing ? $course->branches->pluck('id')->all() : [])))><span>{{ $x->name }}</span></label>
        @endforeach
    </div>
</div>
</x-form-tab>
<x-form-tab name="schedule">
<div class="modal-grid">
    <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="{{ old('start_date',$editing ? optional($course->start_date)->format('Y-m-d') : '') }}"></div>
    <div class="form-group"><label>End Date</label><input type="date" name="end_date" value="{{ old('end_date',$editing ? optional($course->end_date)->format('Y-m-d') : '') }}"></div>
    <div class="form-group"><label>Duration Hours</label><input type="number" min="1" name="duration_hours" value="{{ old('duration_hours',$editing ? $course->duration_hours : '') }}"></div>
    <div class="form-group"><label>Pass Mark % *</label><input type="number" min="0" max="100" step=".01" name="pass_mark" value="{{ old('pass_mark',$editing ? $course->pass_mark : 50) }}" required></div>
    <div class="form-group"><label>Status *</label><select name="status" required>@foreach(['draft','published','archived'] as $status)<option value="{{ $status }}" @selected(old('status',$editing ? $course->status : 'draft')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
    <div class="form-group"><label class="modal-check"><input type="checkbox" name="self_enrolment_enabled" value="1" @checked((bool)old('self_enrolment_enabled',$editing ? $course->self_enrolment_enabled : false))><span>Allow self-enrolment</span></label></div>
    <div class="form-group full">
        <label>Entry assessment (required before self-enrolment)</label>
        <select name="entry_assessment_id">
            <option value="">None — enrol immediately</option>
            @foreach(($assessments ?? collect()) as $assessment)
                <option value="{{ $assessment->id }}" @selected((string)old('entry_assessment_id',$editing ? $course->entry_assessment_id : '')===(string)$assessment->id)>{{ $assessment->title }}</option>
            @endforeach
        </select>
        <small class="form-hint">When set, participants must finish this assessment before they are enrolled.</small>
    </div>
</div>
</x-form-tab>
</x-form-tabs>
