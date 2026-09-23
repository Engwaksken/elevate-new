@extends('layouts.app')
@section('title',($resume->exists?'Edit Resume':'Create Resume').' - ElevateHer360')
@section('content')
<div class="career-page">
<div class="page-header">
    <div>
        <span class="eyebrow">Resume Builder</span>
        <h1>{{ $resume->exists ? 'Edit Resume' : 'Create Resume' }}</h1>
        <p>Build a professional resume section by section. Keep all information accurate and current.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('career.resume.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Resume Centre</a>
        @if($resume->exists)
            <a href="{{ route('career.resume.download',$resume) }}" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Download PDF</a>
        @endif
    </div>
</div>

@if(session('success')) <div class="success-box">{{ session('success') }}</div> @endif
@if(session('warning')) <div class="warning-box">{{ session('warning') }}</div> @endif
@if(session('ai_suggestion'))
    <div class="ai-note">
        <strong><i class="fas fa-wand-magic-sparkles"></i> AI suggestion</strong>
        <p style="white-space:pre-wrap">{{ session('ai_suggestion') }}</p>
        <small>Confirm that suggestions accurately reflect your experience before using them.</small>
    </div>
@endif

<div class="career-workspace">
<div class="eh-tabs" data-eh-tabs>
    <div class="eh-tab-nav">
        <button class="eh-tab-button active" data-eh-tab="details"><i class="fas fa-id-card"></i> Details</button>
        @if($resume->exists)
            <button class="eh-tab-button" data-eh-tab="experience"><i class="fas fa-briefcase"></i> Experience</button>
            <button class="eh-tab-button" data-eh-tab="education"><i class="fas fa-graduation-cap"></i> Education</button>
            <button class="eh-tab-button" data-eh-tab="skills"><i class="fas fa-lightbulb"></i> Skills</button>
            <button class="eh-tab-button" data-eh-tab="ai"><i class="fas fa-wand-magic-sparkles"></i> AI Tools</button>
            <button class="eh-tab-button" data-eh-tab="preview"><i class="fas fa-eye"></i> Preview</button>
        @endif
    </div>

    <div class="eh-tab-content">
        <section class="eh-tab-pane active" data-eh-pane="details">
            <div class="eh-tab-section">
                <div class="eh-tab-section-header">
                    <div><h2>Resume Details</h2><p>Start with a clear title, suitable template and concise professional summary.</p></div>
                </div>
                <div class="career-form-panel">
                    <form method="POST" action="{{ $resume->exists ? route('career.resume.update',$resume) : route('career.resume.store') }}">
                        @csrf
                        @if($resume->exists) @method('PUT') @endif

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">Resume title <span class="required">*</span></label>
                                <input id="title" name="title" value="{{ old('title',$resume->title) }}" required placeholder="e.g. Software Developer Resume">
                                <small class="form-hint">Use a name that helps you identify the job or career direction this resume targets.</small>
                                @error('title')<small class="form-error">{{ $message }}</small>@enderror
                            </div>

                            <div class="form-group">
                                <label for="template">Template <span class="required">*</span></label>
                                <select id="template" name="template" required>
                                    @foreach([
                                        'classic'=>'Classic',
                                        'modern'=>'Modern',
                                        'minimal'=>'Minimal',
                                        'professional'=>'Professional',
                                        'graduate'=>'Graduate',
                                        'technology'=>'Technology',
                                        'executive'=>'Executive'
                                    ] as $key=>$label)
                                        <option value="{{ $key }}" @selected(old('template',$resume->template ?: 'classic')===$key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="form-hint">Changing the template changes presentation only; it does not delete your content.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="professional_summary">Professional summary</label>
                            <textarea id="professional_summary" name="professional_summary" rows="7" placeholder="Summarise your experience, strengths and career goals in 3–5 sentences...">{{ old('professional_summary',$resume->professional_summary) }}</textarea>
                            <small class="form-hint">Focus on relevant strengths, experience and the value you can bring to an employer.</small>
                            @error('professional_summary')<small class="form-error">{{ $message }}</small>@enderror
                        </div>

                        <div class="form-actions">
                            <button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> {{ $resume->exists ? 'Save Changes' : 'Create Resume' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        @if($resume->exists)
        <section class="eh-tab-pane" data-eh-pane="experience">
            <div class="eh-tab-section">
                <div class="eh-tab-section-header"><div><h2>Work Experience</h2><p>Add employment, internship, volunteer and consulting experience that supports your career goals.</p></div></div>
                <div class="eh-data-list">
                    @foreach($resume->experiences as $x)
                        <div class="eh-data-row">
                            <div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-briefcase"></i></span><div class="eh-data-row-copy"><strong>{{ $x->job_title }}</strong><span>{{ $x->organisation }} @if($x->location) · {{ $x->location }} @endif</span></div></div>
                        </div>
                    @endforeach
                </div>

                <div class="career-form-panel spacing-top">
                    <form method="POST" action="{{ route('career.resume.experience.store',$resume) }}">@csrf
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Job title <span class="required">*</span></label>
                                <input name="job_title" required placeholder="e.g. Software Developer">
                                <small class="form-hint">Enter the role or position you held.</small>
                            </div>
                            <div class="form-group">
                                <label>Organisation <span class="required">*</span></label>
                                <input name="organisation" required placeholder="e.g. Women in Technology Uganda">
                                <small class="form-hint">Enter the employer, organisation, business or project name.</small>
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <input name="location" placeholder="e.g. Kampala, Uganda">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="6" placeholder="Describe your responsibilities, achievements and impact..."></textarea>
                            <small class="form-hint">Use clear action verbs and focus on outcomes. Do not include confidential information.</small>
                        </div>
                        <button class="btn btn-primary"><i class="fas fa-plus"></i> Add Experience</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="eh-tab-pane" data-eh-pane="education">
            <div class="eh-tab-section">
                <div class="eh-tab-section-header"><div><h2>Education</h2><p>Add formal education, relevant qualifications and major training.</p></div></div>
                <div class="eh-data-list">
                    @foreach($resume->education as $x)
                        <div class="eh-data-row">
                            <div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-graduation-cap"></i></span><div class="eh-data-row-copy"><strong>{{ $x->qualification }}</strong><span>{{ $x->institution }}</span></div></div>
                        </div>
                    @endforeach
                </div>

                <div class="career-form-panel spacing-top">
                    <form method="POST" action="{{ route('career.resume.education.store',$resume) }}">@csrf
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Institution <span class="required">*</span></label>
                                <input name="institution" required placeholder="e.g. Makerere University">
                                <small class="form-hint">Enter the institution, school or recognised training provider.</small>
                            </div>
                            <div class="form-group">
                                <label>Qualification <span class="required">*</span></label>
                                <input name="qualification" required placeholder="e.g. Bachelor of Information Technology">
                            </div>
                            <div class="form-group">
                                <label>Field of study</label>
                                <input name="field_of_study" placeholder="e.g. Information Systems">
                            </div>
                        </div>
                        <button class="btn btn-primary"><i class="fas fa-plus"></i> Add Education</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="eh-tab-pane" data-eh-pane="skills">
            <div class="eh-tab-section">
                <div class="eh-tab-section-header"><div><h2>Skills</h2><p>Add relevant technical, digital and professional skills.</p></div></div>
                <div class="eh-data-list">
                    @foreach($resume->skills as $x)
                        <div class="eh-data-row">
                            <div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-lightbulb"></i></span><div class="eh-data-row-copy"><strong>{{ $x->skill }}</strong><span>{{ $x->level ?: 'Level not specified' }}</span></div></div>
                        </div>
                    @endforeach
                </div>

                <div class="career-form-panel spacing-top">
                    <form method="POST" action="{{ route('career.resume.skill.store',$resume) }}">@csrf
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Skill <span class="required">*</span></label>
                                <input name="skill" required placeholder="e.g. Laravel, Data Analysis, Project Management">
                                <small class="form-hint">Add one skill at a time for better matching and filtering.</small>
                            </div>
                            <div class="form-group">
                                <label>Skill level</label>
                                <select name="level">
                                    <option value="">Select level</option>
                                    <option value="Beginner">Beginner</option>
                                    <option value="Intermediate">Intermediate</option>
                                    <option value="Advanced">Advanced</option>
                                    <option value="Expert">Expert</option>
                                </select>
                                <small class="form-hint">Choose the level that honestly reflects your current ability.</small>
                            </div>
                        </div>
                        <button class="btn btn-primary"><i class="fas fa-plus"></i> Add Skill</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="eh-tab-pane" data-eh-pane="ai">
            <div class="eh-tab-section">
                <div class="eh-tab-section-header"><div><h2>AI Career Assistant</h2><p>Get suggestions without automatically replacing your resume content.</p></div></div>
                <div class="career-form-panel">
                    <div class="eh-data-list">
                        <div class="eh-data-row">
                            <div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-wand-magic-sparkles"></i></span><div class="eh-data-row-copy"><strong>Improve Resume</strong><span>Review wording, clarity and structure without inventing facts.</span></div></div>
                            <form method="POST" action="{{ route('career.resume.ai.improve',$resume) }}">@csrf<button class="btn btn-primary btn-sm">Analyse</button></form>
                        </div>
                        <div class="eh-data-row">
                            <div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-robot"></i></span><div class="eh-data-row-copy"><strong>ATS Readiness</strong><span>Review headings, readability, keywords and relevant experience.</span></div></div>
                            <form method="POST" action="{{ route('career.resume.ai.ats',$resume) }}">@csrf<button class="btn btn-primary btn-sm">Check</button></form>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('career.resume.ai.tailor',$resume) }}" class="spacing-top">@csrf
                        <div class="form-group">
                            <label>Tailor Resume to Job <span class="required">*</span></label>
                            <textarea name="job_description" rows="9" required placeholder="Paste the job description here..."></textarea>
                            <small class="form-hint">AI compares the job with your existing resume and will not add qualifications or experience you have not provided.</small>
                        </div>
                        <button class="btn btn-primary"><i class="fas fa-bullseye"></i> Compare With Job</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="eh-tab-pane" data-eh-pane="preview">
            <div class="eh-tab-section">
                <div class="eh-tab-section-header"><div><h2>Preview & Download</h2><p>Download the current generated version of your resume.</p></div></div>
                <div class="career-form-panel preview-placeholder">
                    <i class="fas fa-file-pdf"></i>
                    <h3>{{ $resume->title }}</h3>
                    <p>{{ ucfirst($resume->template) }} template</p>
                    <a href="{{ route('career.resume.download',$resume) }}" class="btn btn-primary"><i class="fas fa-download"></i> Download PDF</a>
                </div>
            </div>
        </section>
        @endif
    </div>
</div>
</div>
</div>
@endsection
