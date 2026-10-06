@php
    $useOld = old('_modal') === $id;
    $value = fn (string $field) => $useOld ? old($field) : $department->{$field};
    $isActive = $useOld ? (bool) old('is_active') : ($department->exists ? $department->is_active : true);
@endphp
<div class="eh-modal" id="{{ $id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" @if($useOld && $errors->any()) data-modal-autoopen @endif><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2 id="{{ $id }}-title">{{ $title }}</h2><p>Departments appear in employee and purchase request forms while active.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ $action }}">@csrf @if($method === 'PUT') @method('PUT') @endif
<input type="hidden" name="_modal" value="{{ $id }}">
<div class="eh-modal-body">
@if($useOld && $errors->any())<div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>@endif
<div class="modal-grid">
    <div class="form-group full"><label for="{{ $id }}-name">Name *</label><input id="{{ $id }}-name" name="name" value="{{ $value('name') }}" maxlength="190" placeholder="e.g. Programmes" required>
        @if($department->exists)<small class="form-hint">Renaming also updates purchase requests that use the old name.</small>@endif</div>
    <div class="form-group"><label for="{{ $id }}-code">Code</label><input id="{{ $id }}-code" name="code" value="{{ $value('code') }}" maxlength="50" placeholder="e.g. PROG"><small class="form-hint">Optional short unique code.</small></div>
    <div class="form-group"><label for="{{ $id }}-head">Head of department</label><select id="{{ $id }}-head" name="head_user_id"><option value="">None</option>@foreach($staff as $u)<option value="{{ $u->id }}" @selected((int) $value('head_user_id') === $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <div class="form-group full"><label class="modal-check"><input type="checkbox" name="is_active" value="1" @checked($isActive)><span>Active — show in forms</span></label><small class="form-hint">Inactive departments stay on existing records but can't be chosen for new ones.</small></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">{{ $method === 'POST' ? 'Add Department' : 'Save Changes' }}</button></div>
</form></div></div>
