@php
    $useOld = old('_modal') === $id;
    $value = fn (string $field) => $useOld ? old($field) : $source->{$field};
    $isActive = $useOld ? (bool) old('is_active') : ($source->exists ? $source->is_active : true);
@endphp
<div class="eh-modal" id="{{ $id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" @if($useOld && $errors->any()) data-modal-autoopen @endif><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2 id="{{ $id }}-title">{{ $title }}</h2><p>Active funding sources appear in purchase request, asset and activity forms.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ $action }}">@csrf @if($method === 'PUT') @method('PUT') @endif
<input type="hidden" name="_modal" value="{{ $id }}">
<div class="eh-modal-body">
@if($useOld && $errors->any())<div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>@endif
<div class="modal-grid">
    <div class="form-group full"><label for="{{ $id }}-name">Name *</label><input id="{{ $id }}-name" name="name" value="{{ $value('name') }}" maxlength="190" placeholder="e.g. Mastercard Foundation grant" required>
        @if($source->exists)<small class="form-hint">Renaming also updates purchase requests, assets and activities that use the old name.</small>@endif</div>
    <div class="form-group"><label for="{{ $id }}-code">Code</label><input id="{{ $id }}-code" name="code" value="{{ $value('code') }}" maxlength="50" placeholder="e.g. MCF-2026"><small class="form-hint">Optional short unique code (grant or budget line).</small></div>
    <div class="form-group"><label class="modal-check"><input type="checkbox" name="is_active" value="1" @checked($isActive)><span>Active — show in forms</span></label><small class="form-hint">Inactive sources stay on existing records but can't be chosen for new ones.</small></div>
    <div class="form-group full"><label for="{{ $id }}-description">Description</label><textarea id="{{ $id }}-description" name="description" rows="3" maxlength="2000" placeholder="Donor, grant period, restrictions…">{{ $value('description') }}</textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">{{ $method === 'POST' ? 'Add Funding Source' : 'Save Changes' }}</button></div>
</form></div></div>
