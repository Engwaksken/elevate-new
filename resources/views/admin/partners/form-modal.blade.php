<form method="POST" action="{{ $profile->exists ? route($prefix.'update', $profile) : route($prefix.'store') }}">@csrf @if($profile->exists) @method('PUT') @endif
<input type="hidden" name="_partner_modal" value="{{ $profile->exists ? 'edit-'.$profile->id : 'create' }}">
<div class="eh-modal-body">
@unless($profile->exists)
<div class="form-group"><label>Link existing account (optional)</label><select name="user_id"><option value="">Create a new account using the contact details below</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} · {{ $user->email }}</option>@endforeach</select><small class="form-hint">New accounts can set a password using the password-reset page.</small></div>
@endunless
@include('partials.partner-fields', ['profile'=>$profile, 'type'=>$type, 'fieldLabels'=>$fieldLabels])
<div class="form-group"><label>Status</label><select name="status">@foreach(['approved','pending','rejected'] as $status)<option value="{{ $status }}" @selected(old('status', $profile->status ?? 'approved') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
</div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save details</button></div>
</form>
