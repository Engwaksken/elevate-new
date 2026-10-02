@php($account = $profile->exists ? ($type === 'mentor' ? $profile->user : $profile->owner) : null)
<div class="form-grid">
@foreach(['name' => 'Contact / account name', 'email' => 'Account email', 'phone' => 'Phone', 'country' => 'Country'] as $field => $label)
<div class="form-group"><label for="partner-{{ $field }}">{{ $fieldLabels[$field] ?? $label }}</label><input id="partner-{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" maxlength="{{ $field === 'phone' ? 30 : 190 }}" value="{{ old($field, $field === 'country' ? $profile->country : $account?->{$field}) }}"></div>
@endforeach
@if($type === 'mentor')
@foreach(['organisation'=>'Organisation','job_title'=>'Job title','industry'=>'Industry','years_experience'=>'Years of experience','linkedin_url'=>'LinkedIn URL'] as $field=>$label)
<div class="form-group"><label>{{ $fieldLabels[$field] ?? $label }}</label><input name="{{ $field }}" type="{{ $field === 'years_experience' ? 'number' : ($field === 'linkedin_url' ? 'url' : 'text') }}" value="{{ old($field, $profile->{$field}) }}"></div>
@endforeach
@foreach(['skills'=>'Skills','languages'=>'Languages','mentoring_areas'=>'Mentoring areas'] as $field=>$label)
<div class="form-group"><label>{{ $fieldLabels[$field] ?? $label }} (comma-separated)</label><input name="{{ $field }}_text" value="{{ old($field.'_text', implode(', ', $profile->{$field} ?? [])) }}"></div>
@endforeach
<div class="form-group full"><label>{{ $fieldLabels['professional_bio'] ?? 'Professional biography' }}</label><textarea name="professional_bio" rows="5">{{ old('professional_bio', $profile->professional_bio) }}</textarea></div>
@else
@foreach(['company_name'=>'Company name','company_type'=>'Company type','industry'=>'Industry','website'=>'Website','contact_person'=>'Contact person','location'=>'Location'] as $field=>$label)
<div class="form-group"><label>{{ $fieldLabels[$field] ?? $label }}</label><input name="{{ $field }}" type="{{ $field === 'website' ? 'url' : 'text' }}" value="{{ old($field, $profile->{$field}) }}" @if($field === 'company_name') required @endif></div>
@endforeach
<div class="form-group full"><label>{{ $fieldLabels['description'] ?? 'Company description' }}</label><textarea name="description" rows="5">{{ old('description', $profile->description) }}</textarea></div>
@endif
</div>
