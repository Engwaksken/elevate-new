
@extends('layouts.app')

@section('title', 'Resume Builder - ElevateHer360')

@section('content')

<div class="page-header">
    <div>
        <span class="eyebrow">Career Development</span>

        <h1>Resume Builder</h1>

        <p>
            Create, manage and download your professional resumes.
        </p>
    </div>

    <div class="page-actions">
        <a
            href="{{ route('career.resume.create') }}"
            class="btn btn-primary"
        >
            <i class="fas fa-plus"></i>
            Create Resume
        </a>
    </div>
</div>

<div class="eh-tabs" data-eh-tabs>

    <div class="eh-tab-nav" role="tablist">

        <button
            type="button"
            class="eh-tab-button active"
            data-eh-tab="resumes"
            aria-selected="true"
        >
            <i class="fas fa-file-lines"></i>
            My Resumes
        </button>

        <button
            type="button"
            class="eh-tab-button"
            data-eh-tab="default"
            aria-selected="false"
        >
            <i class="fas fa-star"></i>
            Default Resume
        </button>

        <button
            type="button"
            class="eh-tab-button"
            data-eh-tab="help"
            aria-selected="false"
        >
            <i class="fas fa-circle-info"></i>
            Resume Guide
        </button>

    </div>

    <div class="eh-tab-content">

        <section
            class="eh-tab-pane active"
            data-eh-pane="resumes"
        >

            <div class="eh-tab-section">

                <div class="eh-tab-section-header">
                    <div>
                        <h2>My Resumes</h2>

                        <p>
                            Manage all resumes saved to your account.
                        </p>
                    </div>
                </div>

                <div class="eh-data-list">

                    @forelse($resumes as $resume)

                        <div class="eh-data-row">

                            <div class="eh-data-row-main">

                                <span class="eh-data-row-icon">
                                    <i class="fas fa-file-lines"></i>
                                </span>

                                <div class="eh-data-row-copy">

                                    <strong>
                                        {{ $resume->title }}
                                    </strong>

                                    <span>
                                        {{ ucfirst($resume->template) }} template

                                        @if($resume->is_default)
                                            · Default resume
                                        @endif
                                    </span>

                                </div>

                            </div>

                            <div class="eh-data-row-actions">

                                <a
                                    href="{{ route('career.resume.edit', $resume) }}"
                                    class="btn btn-outline btn-sm"
                                >
                                    <i class="fas fa-pen"></i>
                                    Edit
                                </a>

                                @if(! $resume->is_default)
                                    <form
                                        method="POST"
                                        action="{{ route('career.resume.default', $resume) }}"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="btn btn-outline btn-sm"
                                        >
                                            <i class="fas fa-star"></i>
                                            Make Default
                                        </button>
                                    </form>
                                @endif

                                <a
                                    href="{{ route('career.resume.download', $resume) }}"
                                    class="btn btn-primary btn-sm"
                                >
                                    <i class="fas fa-download"></i>
                                    PDF
                                </a>

                            </div>

                        </div>

                    @empty

                        <div class="eh-empty">

                            <i class="fas fa-file-circle-plus"></i>

                            <h3>No resumes yet</h3>

                            <p>
                                Create your first professional resume and
                                use it when applying for opportunities.
                            </p>

                            <a
                                href="{{ route('career.resume.create') }}"
                                class="btn btn-primary"
                            >
                                <i class="fas fa-plus"></i>
                                Create Resume
                            </a>

                        </div>

                    @endforelse

                </div>

            </div>

        </section>


        <section
            class="eh-tab-pane"
            data-eh-pane="default"
        >

            <div class="eh-tab-section">

                <div class="eh-tab-section-header">
                    <div>
                        <h2>Default Resume</h2>

                        <p>
                            This resume is used as your preferred resume
                            when applying for jobs.
                        </p>
                    </div>
                </div>

                @php
                    $defaultResume =
                        $resumes->firstWhere('is_default', true);
                @endphp

                @if($defaultResume)

                    <div class="eh-data-row">

                        <div class="eh-data-row-main">

                            <span class="eh-data-row-icon">
                                <i class="fas fa-star"></i>
                            </span>

                            <div class="eh-data-row-copy">

                                <strong>
                                    {{ $defaultResume->title }}
                                </strong>

                                <span>
                                    {{ ucfirst($defaultResume->template) }}
                                    template
                                </span>

                            </div>

                        </div>

                        <div class="eh-data-row-actions">

                            <a
                                href="{{ route('career.resume.edit', $defaultResume) }}"
                                class="btn btn-outline btn-sm"
                            >
                                <i class="fas fa-pen"></i>
                                Edit
                            </a>

                            <a
                                href="{{ route('career.resume.download', $defaultResume) }}"
                                class="btn btn-primary btn-sm"
                            >
                                <i class="fas fa-download"></i>
                                Download
                            </a>

                        </div>

                    </div>

                @else

                    <div class="eh-empty">

                        <i class="fas fa-star"></i>

                        <h3>No default resume selected</h3>

                        <p>
                            Choose one of your resumes as the default.
                        </p>

                    </div>

                @endif

            </div>

        </section>


        <section
            class="eh-tab-pane"
            data-eh-pane="help"
        >

            <div class="eh-tab-section">

                <div class="eh-tab-section-header">
                    <div>
                        <h2>Resume Guide</h2>

                        <p>
                            Complete each section to create a stronger
                            professional resume.
                        </p>
                    </div>
                </div>

                <div class="eh-data-list">

                    <div class="eh-data-row">

                        <div class="eh-data-row-main">
                            <span class="eh-data-row-icon">
                                <i class="fas fa-user"></i>
                            </span>

                            <div class="eh-data-row-copy">
                                <strong>Professional Summary</strong>
                                <span>
                                    Briefly explain your experience,
                                    strengths and career goals.
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="eh-data-row">

                        <div class="eh-data-row-main">
                            <span class="eh-data-row-icon">
                                <i class="fas fa-briefcase"></i>
                            </span>

                            <div class="eh-data-row-copy">
                                <strong>Experience</strong>
                                <span>
                                    Add relevant employment, internship
                                    and volunteer experience.
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="eh-data-row">

                        <div class="eh-data-row-main">
                            <span class="eh-data-row-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </span>

                            <div class="eh-data-row-copy">
                                <strong>Education</strong>
                                <span>
                                    Add qualifications and institutions.
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="eh-data-row">

                        <div class="eh-data-row-main">
                            <span class="eh-data-row-icon">
                                <i class="fas fa-lightbulb"></i>
                            </span>

                            <div class="eh-data-row-copy">
                                <strong>Skills</strong>
                                <span>
                                    Add technical and professional skills.
                                </span>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </section>

    </div>

</div>

@endsection