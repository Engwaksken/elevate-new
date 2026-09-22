
@extends('layouts.app')

@section(
    'title',
    ($resume->exists ? 'Edit Resume' : 'Create Resume')
    . ' - ElevateHer360'
)

@section('content')

<div class="page-header">

    <div>
        <span class="eyebrow">Resume Builder</span>

        <h1>
            {{ $resume->exists ? 'Edit Resume' : 'Create Resume' }}
        </h1>

        <p>
            Build your professional resume section by section.
        </p>
    </div>

    <div class="page-actions">

        <a
            href="{{ route('career.resume.index') }}"
            class="btn btn-outline"
        >
            <i class="fas fa-arrow-left"></i>
            My Resumes
        </a>

        @if($resume->exists)

            <a
                href="{{ route('career.resume.download', $resume) }}"
                class="btn btn-primary"
            >
                <i class="fas fa-download"></i>
                Download PDF
            </a>

        @endif

    </div>

</div>


<div class="eh-tabs" data-eh-tabs>

    <div class="eh-tab-nav" role="tablist">

        <button
            type="button"
            class="eh-tab-button active"
            data-eh-tab="details"
            aria-selected="true"
        >
            <i class="fas fa-file-lines"></i>
            Resume Details
        </button>

        @if($resume->exists)

            <button
                type="button"
                class="eh-tab-button"
                data-eh-tab="experience"
                aria-selected="false"
            >
                <i class="fas fa-briefcase"></i>
                Experience
            </button>

            <button
                type="button"
                class="eh-tab-button"
                data-eh-tab="education"
                aria-selected="false"
            >
                <i class="fas fa-graduation-cap"></i>
                Education
            </button>

            <button
                type="button"
                class="eh-tab-button"
                data-eh-tab="skills"
                aria-selected="false"
            >
                <i class="fas fa-lightbulb"></i>
                Skills
            </button>

        @endif

    </div>


    <div class="eh-tab-content">

        <section
            class="eh-tab-pane active"
            data-eh-pane="details"
        >

            <div class="eh-tab-section">

                <div class="eh-tab-section-header">
                    <div>
                        <h2>Resume Details</h2>
                        <p>
                            Set your resume title, template and
                            professional summary.
                        </p>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{
                        $resume->exists
                            ? route('career.resume.update', $resume)
                            : route('career.resume.store')
                    }}"
                >

                    @csrf

                    @if($resume->exists)
                        @method('PUT')
                    @endif


                    <div class="form-grid">

                        <div class="form-group">
                            <label for="resume-title">
                                Resume Title
                            </label>

                            <input
                                id="resume-title"
                                type="text"
                                name="title"
                                value="{{ old('title', $resume->title) }}"
                                placeholder="e.g. Software Developer Resume"
                                required
                            >
                        </div>


                        <div class="form-group">

                            <label for="resume-template">
                                Template
                            </label>

                            <select
                                id="resume-template"
                                name="template"
                                required
                            >

                                <option
                                    value="classic"
                                    @selected(
                                        old('template', $resume->template)
                                        === 'classic'
                                    )
                                >
                                    Classic
                                </option>

                                <option
                                    value="modern"
                                    @selected(
                                        old('template', $resume->template)
                                        === 'modern'
                                    )
                                >
                                    Modern
                                </option>

                                <option
                                    value="minimal"
                                    @selected(
                                        old('template', $resume->template)
                                        === 'minimal'
                                    )
                                >
                                    Minimal
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="professional-summary">
                            Professional Summary
                        </label>

                        <textarea
                            id="professional-summary"
                            name="professional_summary"
                            rows="6"
                            placeholder="Summarise your experience, skills and career goals..."
                        >{{ old(
                            'professional_summary',
                            $resume->professional_summary
                        ) }}</textarea>

                    </div>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fas fa-floppy-disk"></i>

                            {{ $resume->exists
                                ? 'Save Changes'
                                : 'Create Resume'
                            }}
                        </button>

                    </div>

                </form>

            </div>

        </section>


        @if($resume->exists)

            <section
                class="eh-tab-pane"
                data-eh-pane="experience"
            >

                <div class="eh-tab-section">

                    <div class="eh-tab-section-header">
                        <div>
                            <h2>Work Experience</h2>

                            <p>
                                Add your employment, internship or
                                volunteer experience.
                            </p>
                        </div>
                    </div>


                    <form
                        method="POST"
                        action="{{ route(
                            'career.resume.experience.store',
                            $resume
                        ) }}"
                    >

                        @csrf

                        <div class="form-grid">

                            <div class="form-group">

                                <label>Job Title</label>

                                <input
                                    type="text"
                                    name="job_title"
                                    placeholder="e.g. Software Developer"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label>Organisation</label>

                                <input
                                    type="text"
                                    name="organisation"
                                    placeholder="e.g. Women in Technology Uganda"
                                    required
                                >

                            </div>

                        </div>


                        <div class="form-group">

                            <label>Description</label>

                            <textarea
                                name="description"
                                rows="5"
                                placeholder="Describe your responsibilities and achievements..."
                            ></textarea>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fas fa-plus"></i>
                            Add Experience
                        </button>

                    </form>

                </div>

            </section>


            <section
                class="eh-tab-pane"
                data-eh-pane="education"
            >

                <div class="eh-tab-section">

                    <div class="eh-tab-section-header">
                        <div>
                            <h2>Education</h2>

                            <p>
                                Add your academic qualifications.
                            </p>
                        </div>
                    </div>


                    <form
                        method="POST"
                        action="{{ route(
                            'career.resume.education.store',
                            $resume
                        ) }}"
                    >

                        @csrf

                        <div class="form-grid">

                            <div class="form-group">

                                <label>Institution</label>

                                <input
                                    type="text"
                                    name="institution"
                                    placeholder="e.g. Makerere University"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label>Qualification</label>

                                <input
                                    type="text"
                                    name="qualification"
                                    placeholder="e.g. Bachelor of Information Technology"
                                    required
                                >

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fas fa-plus"></i>
                            Add Education
                        </button>

                    </form>

                </div>

            </section>


            <section
                class="eh-tab-pane"
                data-eh-pane="skills"
            >

                <div class="eh-tab-section">

                    <div class="eh-tab-section-header">
                        <div>
                            <h2>Skills</h2>

                            <p>
                                Add relevant technical and professional
                                skills.
                            </p>
                        </div>
                    </div>


                    <form
                        method="POST"
                        action="{{ route(
                            'career.resume.skill.store',
                            $resume
                        ) }}"
                    >

                        @csrf

                        <div class="form-grid">

                            <div class="form-group">

                                <label>Skill</label>

                                <input
                                    type="text"
                                    name="skill"
                                    placeholder="e.g. Laravel Development"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label>Level</label>

                                <input
                                    type="text"
                                    name="level"
                                    placeholder="e.g. Advanced"
                                >

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fas fa-plus"></i>
                            Add Skill
                        </button>

                    </form>

                </div>

            </section>

        @endif

    </div>

</div>

@endsection