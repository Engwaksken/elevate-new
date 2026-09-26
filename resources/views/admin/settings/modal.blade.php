<div class="eh-modal" id="{{ $id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>{{ $title }}</h2><p>Configure a platform setting.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ $action }}">@csrf @if($method==='PUT') @method('PUT') @endif
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Group *</label><input name="group" value="{{ $setting->group ?: 'general' }}" placeholder="e.g. general, email, ai, calendar" required><small class="form-hint">Required. Use a short category for related settings.</small></div>
<div class="form-group"><label>Key *</label><input name="key" value="{{ $setting->key }}" placeholder="e.g. site_name" required><small class="form-hint">Required. Use a unique machine-friendly key.</small></div>
<div class="form-group"><label>Type *</label><select name="type">@foreach(['string','boolean','integer','float','json'] as $type)<option value="{{ $type }}" @selected(($setting->type ?: 'string')===$type)>{{ ucfirst($type) }}</option>@endforeach</select><small class="form-hint">Choose how the application should interpret the stored value.</small></div>
<div class="form-group full"><label>Value</label><textarea name="value" rows="5" placeholder="Enter the setting value...">{{ $setting->is_encrypted ? '' : $setting->value }}</textarea><small class="form-hint">{{ $setting->is_encrypted ? 'Encrypted values are not displayed. Enter a new value only when replacing it.' : 'For JSON type, enter valid JSON.' }}</small></div>
<div class="form-group full">
<div class="eh-choice-grid">
<label class="eh-choice-card"><input type="checkbox" name="is_public" value="1" @checked($setting->is_public)><span class="eh-choice-card__text">Public setting</span></label>
<label class="eh-choice-card"><input type="checkbox" name="is_encrypted" value="1" @checked($setting->is_encrypted)><span class="eh-choice-card__text">Encrypt stored value</span></label>
</div>
</div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">{{ $method==='POST' ? 'Save Setting':'Save Changes' }}</button></div>
</form></div></div>