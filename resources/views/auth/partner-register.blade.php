@extends('layouts.app')

@php
    $isMentor = $type === 'mentor';
    $label = $isMentor ? 'Mentor' : 'Employer';
    $title = $content['title'] ?? ($isMentor ? 'Become a Mentor' : 'Register an Employer');
    $summary = $content['summary'] ?? 'Submit your details for administrator review.';
    $fieldLabels = data_get($content, 'settings.field_labels', []);
@endphp

@section('title', $title.' | ElevateHer360')
@section('meta_description', $summary)

@section('content')

<div class="eh-register-shell">

    {{-- LEFT PANEL --}}
    <section class="eh-register-hero">
        <div class="eh-register-hero-inner">
            <div class="eh-register-gold-line"></div>

            <span class="eh-register-badge">
                <i class="fas {{ $isMentor ? 'fa-user-tie' : 'fa-building' }}"></i>
                {{ strtoupper($label) }} REGISTRATION
            </span>

            <h1>
                {{ $isMentor ? 'Share your experience as a mentor.' : 'Partner with us as an employer.' }}
            </h1>

            <p>
                {{ $isMentor
                    ? 'Guide participants through mentorship sessions, set goals and help them grow into their careers.'
                    : 'Post opportunities, review applicants and connect with skilled participants ready for work.' }}
            </p>

            <div class="eh-register-benefits">
                <div class="eh-register-benefit">
                    <span><i class="fas {{ $isMentor ? 'fa-handshake' : 'fa-briefcase' }}"></i></span>
                    <div>
                        <strong>{{ $isMentor ? 'Mentor participants' : 'Post opportunities' }}</strong>
                        <small>{{ $isMentor ? 'Run sessions and track mentee progress.' : 'Publish jobs and manage applications.' }}</small>
                    </div>
                </div>

                <div class="eh-register-benefit">
                    <span><i class="fas {{ $isMentor ? 'fa-bullseye' : 'fa-users' }}"></i></span>
                    <div>
                        <strong>{{ $isMentor ? 'Set growth goals' : 'Meet talent' }}</strong>
                        <small>{{ $isMentor ? 'Support goals and session reports.' : 'Review applicants and shortlist candidates.' }}</small>
                    </div>
                </div>

                <div class="eh-register-benefit">
                    <span><i class="fas fa-chart-line"></i></span>
                    <div>
                        <strong>Track impact</strong>
                        <small>See engagement and placement outcomes.</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FORM PANEL --}}
    <section class="eh-register-form-side">
        <div class="eh-register-form-inner">
            <div class="eh-register-heading">
                <div>
                    <span class="eh-register-step-count">Create account</span>
                    <h2>{{ $title }}</h2>
                    <p>{{ $summary }}</p>
                </div>
            </div>

            @include('partials.form-feedback')
            @include('partials.cms-intro', ['slug' => $type.'-signup'])

            @if(data_get($content, 'settings.signup_open', true))
                @php
                    $account = $profile->exists ? ($isMentor ? $profile->user : $profile->owner) : null;
                    $partnerFormTabs = [
                        'account' => ['label' => 'Account', 'icon' => 'fa-id-card', 'fields' => ['name', 'email', 'phone', 'country']],
                        'profile' => ['label' => $isMentor ? 'Mentor profile' : 'Company', 'icon' => $isMentor ? 'fa-user-tie' : 'fa-building', 'fields' => $isMentor
                            ? ['organisation', 'job_title', 'industry', 'years_experience', 'linkedin_url', 'skills_text', 'languages_text', 'mentoring_areas_text', 'professional_bio']
                            : ['company_name', 'company_type', 'industry', 'website', 'contact_person', 'location', 'description']],
                        'security' => ['label' => 'Security', 'icon' => 'fa-lock', 'fields' => ['password', 'password_confirmation', 'consent']],
                    ];
                @endphp
                <form method="POST" action="{{ route('public.partners.'.$type.'.store') }}" class="eh-register-form">
                    @csrf

                    <x-form-tabs id="partner-{{ $type }}" label="{{ $label }} registration" :tabs="$partnerFormTabs">
                        <x-form-tab name="account">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="partner-name">{{ $fieldLabels['name'] ?? 'Contact / account name' }} <span aria-hidden="true">*</span></label>
                                    <input id="partner-name" name="name" type="text" maxlength="190" value="{{ old('name', $account?->name) }}" placeholder="e.g. Jane Nantongo" required>
                                </div>
                                <div class="form-group">
                                    <label for="partner-email">{{ $fieldLabels['email'] ?? 'Account email' }} <span aria-hidden="true">*</span></label>
                                    <input id="partner-email" name="email" type="email" maxlength="190" value="{{ old('email', $account?->email) }}" placeholder="e.g. name@example.com" required>
                                </div>
                                <div class="form-group">
                                    <label for="partner-phone">{{ $fieldLabels['phone'] ?? 'Phone' }}</label>
                                    <input id="partner-phone" name="phone" type="text" maxlength="30" value="{{ old('phone', $account?->phone) }}" placeholder="e.g. +256 700 000 000">
                                </div>
                                <div class="form-group">
                                    <label for="partner-country">{{ $fieldLabels['country'] ?? 'Country' }}</label>
                                    <input id="partner-country" name="country" type="text" maxlength="190" value="{{ old('country', $profile->country) }}" placeholder="e.g. Uganda">
                                </div>
                            </div>
                        </x-form-tab>

                        <x-form-tab name="profile">
                            <div class="form-grid">
                                @if($isMentor)
                                    <div class="form-group"><label>{{ $fieldLabels['organisation'] ?? 'Organisation' }}</label><input name="organisation" type="text" value="{{ old('organisation', $profile->organisation) }}" placeholder="e.g. Acme Technologies"></div>
                                    <div class="form-group"><label>{{ $fieldLabels['job_title'] ?? 'Job title' }}</label><input name="job_title" type="text" value="{{ old('job_title', $profile->job_title) }}" placeholder="e.g. Senior Software Engineer"></div>
                                    <div class="form-group"><label>{{ $fieldLabels['industry'] ?? 'Industry' }}</label><input name="industry" type="text" value="{{ old('industry', $profile->industry) }}" placeholder="e.g. Technology"></div>
                                    <div class="form-group"><label>{{ $fieldLabels['years_experience'] ?? 'Years of experience' }}</label><input name="years_experience" type="number" value="{{ old('years_experience', $profile->years_experience) }}" placeholder="e.g. 5"></div>
                                    <div class="form-group full"><label>{{ $fieldLabels['linkedin_url'] ?? 'LinkedIn URL' }}</label><input name="linkedin_url" type="url" value="{{ old('linkedin_url', $profile->linkedin_url) }}" placeholder="https://www.linkedin.com/in/username"></div>
                                    <div class="form-group full"><label>{{ $fieldLabels['skills'] ?? 'Skills' }} (comma-separated)</label><input name="skills_text" value="{{ old('skills_text', implode(', ', $profile->skills ?? [])) }}" placeholder="e.g. Leadership, Public speaking, Project management"></div>
                                    <div class="form-group full"><label>{{ $fieldLabels['languages'] ?? 'Languages' }} (comma-separated)</label><input name="languages_text" value="{{ old('languages_text', implode(', ', $profile->languages ?? [])) }}" placeholder="e.g. English, Luganda"></div>
                                    <div class="form-group full"><label>{{ $fieldLabels['mentoring_areas'] ?? 'Mentoring areas' }} (comma-separated)</label><input name="mentoring_areas_text" value="{{ old('mentoring_areas_text', implode(', ', $profile->mentoring_areas ?? [])) }}" placeholder="e.g. Career growth, Interview preparation"></div>
                                    <div class="form-group full"><label>{{ $fieldLabels['professional_bio'] ?? 'Professional biography' }}</label><textarea name="professional_bio" rows="5" placeholder="Tell us briefly about your background and experience...">{{ old('professional_bio', $profile->professional_bio) }}</textarea></div>
                                @else
                                    <div class="form-group full"><label>{{ $fieldLabels['company_name'] ?? 'Company name' }} <span aria-hidden="true">*</span></label><input name="company_name" type="text" value="{{ old('company_name', $profile->company_name) }}" placeholder="e.g. Acme Uganda Ltd" required></div>
                                    <div class="form-group"><label>{{ $fieldLabels['company_type'] ?? 'Company type' }}</label><input name="company_type" type="text" value="{{ old('company_type', $profile->company_type) }}" placeholder="e.g. Private limited company"></div>
                                    <div class="form-group"><label>{{ $fieldLabels['industry'] ?? 'Industry' }}</label><input name="industry" type="text" value="{{ old('industry', $profile->industry) }}" placeholder="e.g. Financial services"></div>
                                    <div class="form-group full"><label>{{ $fieldLabels['website'] ?? 'Website' }}</label><input name="website" type="url" value="{{ old('website', $profile->website) }}" placeholder="https://www.example.com"></div>
                                    <div class="form-group"><label>{{ $fieldLabels['contact_person'] ?? 'Contact person' }}</label><input name="contact_person" type="text" value="{{ old('contact_person', $profile->contact_person) }}" placeholder="e.g. John Okello"></div>
                                    <div class="form-group"><label>{{ $fieldLabels['location'] ?? 'Location' }}</label><input name="location" type="text" value="{{ old('location', $profile->location) }}" placeholder="e.g. Kampala, Uganda"></div>
                                    <div class="form-group full"><label>{{ $fieldLabels['description'] ?? 'Company description' }}</label><textarea name="description" rows="5" placeholder="Tell us about your organisation...">{{ old('description', $profile->description) }}</textarea></div>
                                @endif
                            </div>
                        </x-form-tab>

                        <x-form-tab name="security">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="partner-password">Password <span aria-hidden="true">*</span></label>
                                    <div class="eh-login-input-wrap">
                                        <i class="fas fa-lock"></i>
                                        <input id="partner-password" type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Create a strong password">
                                        <button type="button" class="eh-login-password-toggle" data-password-toggle="partner-password" aria-label="Show or hide password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-hint">Minimum 8 characters with mixed case and a number.</small>
                                </div>

                                <div class="form-group">
                                    <label for="partner-password-confirmation">Confirm password <span aria-hidden="true">*</span></label>
                                    <input id="partner-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat your password">
                                </div>
                            </div>

                            <label class="modal-check">
                                <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                                <span>I agree to the <a href="{{ route('legal.privacy') }}">Privacy Policy</a> and <a href="{{ route('legal.terms') }}">Terms of Use</a>.</span>
                            </label>
                        </x-form-tab>
                    </x-form-tabs>

                    <button class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> {{ data_get($content, 'settings.button_label') ?: 'Submit registration' }}
                    </button>
                </form>

                <div class="eh-login-divider"><span>Already registered?</span></div>

                <a href="{{ route('partners.'.$type.'.login') }}" class="eh-login-create">
                    <i class="fas fa-right-to-bracket"></i>
                    Sign in to your {{ strtolower($label) }} account
                </a>
            @else
                <div class="info-box">Registrations are currently closed.</div>
            @endif
        </div>
    </section>
</div>

@endsection
