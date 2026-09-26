<div class="eh-modal" id="{{ $id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>{{ $title }}</h2><p>Enter the branch location details.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ $action }}">@csrf @if($method==='PUT') @method('PUT') @endif
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Name *</label><input name="name" value="{{ $branch->name }}" placeholder="e.g. Kampala Branch" required><small class="form-hint">Required. Use the official branch name.</small></div>
<div class="form-group"><label>Code</label><input name="code" value="{{ $branch->code }}" placeholder="e.g. KLA"><small class="form-hint">Optional. Enter a short unique branch code.</small></div>
<div class="form-group"><label>District</label><input name="district" value="{{ $branch->district }}" placeholder="e.g. Kampala"><small class="form-hint">Optional. Enter the district where this branch operates.</small></div>
<div class="form-group"><label>Country *</label><input name="country" value="{{ $branch->country ?: 'Uganda' }}" placeholder="e.g. Uganda" required><small class="form-hint">Required. Enter the country for this branch.</small></div>
<div class="form-group"><label class="modal-check"><input type="checkbox" name="is_active" value="1" @checked($branch->exists ? $branch->is_active : true)><span>Active branch</span></label><small class="form-hint">Active branches remain available for programme and cohort assignment.</small></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">{{ $method==='POST' ? 'Create Branch':'Save Changes' }}</button></div>
</form></div></div>