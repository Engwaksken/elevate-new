@extends('layouts.app')
@section('title','Profile - ElevateHer360')
@section('content')
@php
    $profile = $user->profile;
    $selectedDisabilities = old('disability_types', $profile->disability_types ?? []);
@endphp

<div class="career-page">
<div class="page-header">
    <div>
        <span class="eyebrow">Account</span>
        <h1>My Profile</h1>
        <p>Keep your personal, location, accessibility and career information up to date.</p>
    </div>
</div>

<form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('PUT')

    <input type="hidden" name="name" value="{{ old('name',$user->name) }}">
    <input type="hidden" name="email" value="{{ old('email',$user->email) }}">
    <input type="hidden" name="phone" value="{{ old('phone',$user->phone) }}">

    <div class="career-workspace">
    <div class="eh-tabs" data-eh-tabs>
        <div class="eh-tab-nav">
            <button type="button" class="eh-tab-button active" data-eh-tab="personal"><i class="fas fa-user"></i> Personal</button>
            <button type="button" class="eh-tab-button" data-eh-tab="location"><i class="fas fa-location-dot"></i> Location & Accessibility</button>
            <button type="button" class="eh-tab-button" data-eh-tab="career"><i class="fas fa-briefcase"></i> Career</button>
            <button type="button" class="eh-tab-button" data-eh-tab="preferences"><i class="fas fa-sliders"></i> Preferences</button>
        </div>

        <div class="eh-tab-content">
            <section class="eh-tab-pane active" data-eh-pane="personal">
                <div class="eh-tab-section">
                    <div class="eh-tab-section-header"><div><h2>Personal Information</h2><p>Use the names and details you want associated with your participant profile.</p></div></div>
                    <div class="career-form-panel">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Surname</label>
                                <input name="surname" value="{{ old('surname',$profile->surname ?? '') }}" placeholder="e.g. Nansubuga">
                                <small class="form-hint">Enter your family or surname.</small>
                            </div>
                            <div class="form-group">
                                <label>Given name</label>
                                <input name="given_name" value="{{ old('given_name',$profile->given_name ?? '') }}" placeholder="e.g. Sarah">
                                <small class="form-hint">Enter your first or given name.</small>
                            </div>
                            <div class="form-group">
                                <label>Other name</label>
                                <input name="other_name" value="{{ old('other_name',$profile->other_name ?? '') }}" placeholder="Enter another name if applicable">
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender">
                                    <option value="">Select gender</option>
                                    @foreach(['female'=>'Female','male'=>'Male','other'=>'Other','prefer_not_to_say'=>'Prefer not to say'] as $v=>$l)
                                        <option value="{{ $v }}" @selected(old('gender',$profile->gender ?? '')===$v)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date of birth</label>
                                <input type="date" name="date_of_birth" value="{{ old('date_of_birth',optional($profile->date_of_birth ?? null)->format('Y-m-d')) }}">
                            </div>
                            @if(isset($branches))
                            <div class="form-group">
                                <label>Branch</label>
                                <select name="branch_id">
                                    <option value="">Select branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string)old('branch_id',$profile->branch_id ?? '')===(string)$branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-hint">Choose the ElevateHer360 branch or programme location linked to you.</small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="eh-tab-pane" data-eh-pane="location">
                <div class="eh-tab-section">
                    <div class="eh-tab-section-header"><div><h2>Location & Accessibility</h2><p>Provide location details and, if relevant, accessibility information that can help us support your participation.</p></div></div>
                    <div class="career-form-panel">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Country</label>
                                <input name="country" value="{{ old('country',$profile->country ?? '') }}" placeholder="e.g. Uganda">
                            </div>
                            <div class="form-group">
                                <label>District</label>
                                <input name="district" value="{{ old('district',$profile->district ?? '') }}" placeholder="e.g. Kampala, Wakiso, Gulu">
                            </div>
                            <div class="form-group full">
                                <label>Location</label>
                                <input name="location" value="{{ old('location',$profile->location ?? '') }}" placeholder="e.g. Bukoto, Kampala">
                                <small class="form-hint">Enter your town, city, community or other useful location information.</small>
                            </div>
                        </div>

                        <div class="pwd-panel">
                            <label class="check-row">
                                <input type="checkbox" id="is_pwd_checkbox" name="is_pwd" value="1" @checked((bool)old('is_pwd',$profile->is_pwd ?? false))>
                                <span>
                                    <strong>Person with Disability (PWD)</strong>
                                    <small>Select this if you identify as a person with disability and want to provide relevant accessibility information.</small>
                                </span>
                            </label>

                            <div id="pwd_details" class="pwd-details">
                                <h3>Select disability type</h3>
                                <p class="section-hint">You may select more than one option.</p>

                                <div class="check-grid">
                                    @foreach([
                                        'visual'=>'Visual impairment',
                                        'hearing'=>'Hearing impairment',
                                        'physical'=>'Physical disability',
                                        'intellectual'=>'Intellectual disability',
                                        'psychosocial'=>'Psychosocial disability',
                                        'speech'=>'Speech / communication disability',
                                        'multiple'=>'Multiple disabilities',
                                        'other'=>'Other',
                                    ] as $key=>$label)
                                        <label class="check-option">
                                            <input type="checkbox" name="disability_types[]" value="{{ $key }}" @checked(in_array($key,$selectedDisabilities,true))>
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                @error('disability_types')<small class="form-error">{{ $message }}</small>@enderror

                                <div id="disability_other_wrap" class="form-group spacing-top">
                                    <label for="disability_other">Please specify other disability <span class="required">*</span></label>
                                    <input id="disability_other" name="disability_other" value="{{ old('disability_other',$profile->disability_other ?? '') }}" placeholder="Please specify">
                                    <small class="form-hint">Only complete this field when “Other” is selected.</small>
                                    @error('disability_other')<small class="form-error">{{ $message }}</small>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="eh-tab-pane" data-eh-pane="career">
                <div class="eh-tab-section">
                    <div class="eh-tab-section-header"><div><h2>Career Information</h2><p>Help ElevateHer360 tailor learning, mentorship and opportunity recommendations.</p></div></div>
                    <div class="career-form-panel">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Education level</label>
                                <input name="education_level" value="{{ old('education_level',$profile->education_level ?? '') }}" placeholder="e.g. Diploma, Bachelor's degree">
                            </div>
                            <div class="form-group">
                                <label>Employment status</label>
                                <input name="employment_status" value="{{ old('employment_status',$profile->employment_status ?? '') }}" placeholder="e.g. Student, Employed, Self-employed">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Career interests</label>
                            <textarea name="career_interests" rows="6" placeholder="e.g. Software development, data analysis, digital marketing, entrepreneurship...">{{ old('career_interests',$profile->career_interests ?? '') }}</textarea>
                            <small class="form-hint">Separate several interests with commas or short phrases.</small>
                        </div>
                    </div>
                </div>
            </section>

            <section class="eh-tab-pane" data-eh-pane="preferences">
                <div class="eh-tab-section">
                    <div class="eh-tab-section-header"><div><h2>Preferences</h2><p>Choose your preferred communication and interface options.</p></div></div>
                    <div class="career-form-panel">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Preferred language</label>
                                <select name="preferred_language">
                                    <option value="en" @selected(old('preferred_language',$profile->preferred_language ?? 'en')==='en')>English</option>
                                    <option value="lg" @selected(old('preferred_language',$profile->preferred_language ?? '')==='lg')>Luganda</option>
                                    <option value="sw" @selected(old('preferred_language',$profile->preferred_language ?? '')==='sw')>Kiswahili</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="form-actions sticky-actions">
        <button class="btn btn-primary" type="submit"><i class="fas fa-floppy-disk"></i> Save Profile</button>
    </div>
    </div>
</form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const pwd = document.getElementById('is_pwd_checkbox');
    const details = document.getElementById('pwd_details');
    const otherWrap = document.getElementById('disability_other_wrap');
    const other = document.querySelector('input[name="disability_types[]"][value="other"]');

    function syncPwd() {
        const on = !!pwd?.checked;
        if (details) details.hidden = !on;
        syncOther();
    }

    function syncOther() {
        const show = !!pwd?.checked && !!other?.checked;
        if (otherWrap) otherWrap.hidden = !show;
        const input = document.getElementById('disability_other');
        if (input) input.required = show;
    }

    pwd?.addEventListener('change', syncPwd);
    other?.addEventListener('change', syncOther);
    syncPwd();
});
</script>
@endpush
@endsection
