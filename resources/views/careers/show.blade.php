@extends('careers.layout')
@section('title', $vacancy->title)
@section('content')
@php
    $input = $input ?? [];
    $val = fn (string $key, $default = '') => old($key, data_get($input, $key, $default));
    $host = request('host', data_get($input, 'host'));
    $open = $vacancy->isAcceptingApplications();
    $questions = $vacancy->application_questions ?? [];
@endphp

@if($embed)
    <p style="margin:0 0 10px"><a href="{{ route('careers.embed', ['host' => $host]) }}">&larr; All positions</a></p>
@else
    <p style="margin:0 0 10px"><a href="{{ route('careers.index') }}">&larr; All positions</a></p>
@endif

<article class="c-card">
    <h1 style="margin:0 0 6px;font-size:1.6rem;line-height:1.25">{{ $vacancy->title }}</h1>
    <div class="c-meta">
        @if($vacancy->reference)<span>Ref: {{ $vacancy->reference }}</span>@endif
        @if($vacancy->department)<span>{{ $vacancy->department->name }}</span>@endif
        @if($vacancy->location)<span>{{ $vacancy->location }}</span>@endif
        @if($vacancy->employment_type)<span>{{ $vacancy->employment_type }}</span>@endif
        @if($vacancy->salary_range)<span>{{ $vacancy->salary_range }}</span>@endif
        @if($vacancy->positions > 1)<span>{{ $vacancy->positions }} positions</span>@endif
        @if($vacancy->closing_date)<span><strong>Closes {{ $vacancy->closing_date->format('d M Y') }}</strong></span>@endif
    </div>
    @if($vacancy->summary)<p>{{ $vacancy->summary }}</p>@endif
    <div class="c-section">
        @if($vacancy->description)<h3>About the role</h3><div class="c-rich">{{ $vacancy->description }}</div>@endif
        @if($vacancy->responsibilities)<h3>Key responsibilities</h3><div class="c-rich">{{ $vacancy->responsibilities }}</div>@endif
        @if($vacancy->requirements)<h3>Requirements</h3><div class="c-rich">{{ $vacancy->requirements }}</div>@endif
        @if(! empty($vacancy->skills))<h3>Skills</h3><p>@foreach($vacancy->skills as $skill)<span class="c-pill">{{ $skill }}</span> @endforeach</p>@endif
    </div>
</article>

<section class="c-card" id="apply" aria-labelledby="apply-title">
    <h2 id="apply-title" style="margin:0 0 4px">Apply for this position</h2>
    @if(! $open)
        <div class="c-alert" role="status">This vacancy is closed and no longer accepting applications.</div>
    @else
        <p class="c-hint" style="margin:0 0 14px">Fields marked * are required.</p>
        @if($errors->any())
            <div class="c-alert" role="alert">Please correct the highlighted fields and try again.@if($errors->has('cv')) Please attach your CV again.@endif</div>
        @endif
        <form method="POST" action="{{ route('careers.apply', $vacancy->slug) }}" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="embed" value="{{ $embed ? 1 : 0 }}">
            <input type="hidden" name="host" value="{{ $host }}">
            <input type="hidden" name="started" value="{{ time() }}">
            <div class="c-hp" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>

            <div class="c-grid">
                <div class="c-field"><label for="a-name">Full name *</label><input id="a-name" name="name" value="{{ $val('name') }}" required autocomplete="name">@error('name')<div class="c-error">{{ $message }}</div>@enderror</div>
                <div class="c-field"><label for="a-email">Email *</label><input id="a-email" type="email" name="email" value="{{ $val('email') }}" required autocomplete="email">@error('email')<div class="c-error">{{ $message }}</div>@enderror</div>
                <div class="c-field"><label for="a-phone">Phone *</label><input id="a-phone" name="phone" value="{{ $val('phone') }}" required autocomplete="tel">@error('phone')<div class="c-error">{{ $message }}</div>@enderror</div>
                <div class="c-field"><label for="a-location">Current location</label><input id="a-location" name="location" value="{{ $val('location') }}" autocomplete="address-level2"></div>
                <div class="c-field"><label for="a-edu">Highest education *</label>
                    <select id="a-edu" name="education_level" required><option value="">Select</option>
                        @foreach(\App\Models\RecruitmentVacancy::EDUCATION_LEVELS as $key => $label)<option value="{{ $key }}" @selected($val('education_level') === $key)>{{ $label }}</option>@endforeach
                    </select>@error('education_level')<div class="c-error">{{ $message }}</div>@enderror</div>
                <div class="c-field"><label for="a-exp">Years of relevant experience *</label><input id="a-exp" type="number" min="0" max="60" name="years_experience" value="{{ $val('years_experience') }}" required>@error('years_experience')<div class="c-error">{{ $message }}</div>@enderror</div>
            </div>
            <div class="c-field"><label for="a-linkedin">LinkedIn or portfolio link</label><input id="a-linkedin" type="url" name="linkedin_url" value="{{ $val('linkedin_url') }}" placeholder="https://">@error('linkedin_url')<div class="c-error">{{ $message }}</div>@enderror</div>

            <div class="c-field"><label for="a-cv">CV / Resume {{ $vacancy->cv_required ? '*' : '' }}</label>
                <input id="a-cv" type="file" name="cv" accept=".pdf,.doc,.docx" {{ $vacancy->cv_required ? 'required' : '' }}>
                <span class="c-hint">PDF or Word, up to 5 MB. A text-based PDF helps us read your CV accurately.</span>
                @error('cv')<div class="c-error">{{ $message }}</div>@enderror</div>

            @foreach($questions as $i => $question)
                <div class="c-field"><label for="a-q{{ $i }}">{{ $question }} *</label><textarea id="a-q{{ $i }}" name="answers[{{ $i }}]" required style="min-height:90px">{{ $val('answers.'.$i) }}</textarea>@error('answers.'.$i)<div class="c-error">{{ $message }}</div>@enderror</div>
            @endforeach

            <div class="c-field"><label for="a-cover">Cover letter</label><textarea id="a-cover" name="cover_letter" placeholder="Tell us why you are a great fit for this role">{{ $val('cover_letter') }}</textarea>@error('cover_letter')<div class="c-error">{{ $message }}</div>@enderror</div>

            <div class="c-field"><label class="c-check"><input type="checkbox" name="consent" value="1" @checked($val('consent'))> <span>I agree that my application data may be stored and processed, including automated screening against the job criteria, for recruitment purposes. A person makes every hiring decision.</span></label>@error('consent')<div class="c-error">{{ $message }}</div>@enderror</div>

            <button class="c-btn">Submit application</button>
        </form>
    @endif
</section>
@endsection
