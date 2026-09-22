@extends('layouts.app')

@section('title', 'ElevateHer360 | Learn, Connect and Grow')

@section(
    'meta_description',
    'ElevateHer360 brings learning, mentorship, career development, job opportunities and digital resources together in one platform.'
)

@section('content')

<section class="hero full-width-section">

    <div class="container hero-grid">

        <div class="hero-copy">

            <div class="eyebrow">
                Your complete growth journey
            </div>

            <h1>
                Learn, connect, build your career
                and move into opportunity.
            </h1>

            <p class="lead">
                ElevateHer360 brings training, mentorship,
                career development, employment opportunities
                and digital resources together under one
                participant account.
            </p>

            <div class="hero-actions">

                @auth

                    @if(auth()->user()->isStaff())

                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="btn btn-primary btn-lg"
                        >
                            <i class="fas fa-gauge-high"></i>
                            Open Dashboard
                        </a>

                    @else

                        <a
                            href="{{ route('dashboard') }}"
                            class="btn btn-primary btn-lg"
                        >
                            <i class="fas fa-gauge-high"></i>
                            Continue to Dashboard
                        </a>

                    @endif

                @else

                    <a
                        href="{{ route('register') }}"
                        class="btn btn-primary btn-lg"
                    >
                        <i class="fas fa-user-plus"></i>
                        Create Participant Account
                    </a>

                    <a
                        href="{{ route('login') }}"
                        class="btn btn-outline btn-lg"
                    >
                        <i class="fas fa-right-to-bracket"></i>
                        Participant Sign In
                    </a>

                @endauth

            </div>

        </div>


        <div class="journey-card">

            <div class="eyebrow">
                One connected journey
            </div>

            <h2>
                From registration to outcomes
            </h2>

            <div class="journey-item">

                <span class="number">1</span>

                <div>
                    <strong>Learn</strong>
                    <br>
                    Courses, assessments, attendance and certificates
                </div>

            </div>

            <div class="journey-item">

                <span class="number">2</span>

                <div>
                    <strong>Connect</strong>
                    <br>
                    Mentors, goals, sessions and milestones
                </div>

            </div>

            <div class="journey-item">

                <span class="number">3</span>

                <div>
                    <strong>Prepare</strong>
                    <br>
                    Career coaching, skills and resume building
                </div>

            </div>

            <div class="journey-item">

                <span class="number">4</span>

                <div>
                    <strong>Progress</strong>
                    <br>
                    Job applications, employment and outcome tracking
                </div>

            </div>

        </div>

    </div>

</section>


<section
    class="section full-width-section section-light"
    id="platform"
>

    <div class="container">

        <div class="section-header">

            <div class="eyebrow">
                Everything in one place
            </div>

            <h2>
                One participant. One platform.
            </h2>

            <p>
                Your learning, mentorship, career and employment
                journey stays connected to one profile.
            </p>

        </div>


        <div class="features">

            <a
                href="{{ route('learning.index') }}"
                class="feature-link"
            >
                <article class="feature">

                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>

                    <h3>
                        Learning
                    </h3>

                    <p>
                        Explore courses, lessons, assessments,
                        attendance, progress tracking and certificates.
                    </p>

                    <div class="feature-arrow">
                        Explore Learning
                        <i class="fas fa-arrow-right"></i>
                    </div>

                </article>
            </a>


            @auth

                <a
                    href="{{ route('mentorship.dashboard') }}"
                    class="feature-link"
                >

            @else

                <a
                    href="{{ route('login') }}"
                    class="feature-link"
                >

            @endauth

                <article class="feature">

                    <div class="feature-icon">
                        <i class="fas fa-handshake"></i>
                    </div>

                    <h3>
                        Mentorship
                    </h3>

                    <p>
                        Connect with mentors, manage goals,
                        sessions, milestones and action plans.
                    </p>

                    <div class="feature-arrow">
                        Open Mentorship
                        <i class="fas fa-arrow-right"></i>
                    </div>

                </article>

            </a>


            <a
                href="{{ route('jobs.index') }}"
                class="feature-link"
            >
                <article class="feature">

                    <div class="feature-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>

                    <h3>
                        Jobs
                    </h3>

                    <p>
                        Discover opportunities, save jobs,
                        apply and track your application journey.
                    </p>

                    <div class="feature-arrow">
                        View Jobs
                        <i class="fas fa-arrow-right"></i>
                    </div>

                </article>
            </a>


            <a
                href="{{ route('library.index') }}"
                class="feature-link"
            >
                <article class="feature">

                    <div class="feature-icon">
                        <i class="fas fa-book-open"></i>
                    </div>

                    <h3>
                        Digital Library
                    </h3>

                    <p>
                        Access curated learning, career and
                        professional development resources.
                    </p>

                    <div class="feature-arrow">
                        Browse Library
                        <i class="fas fa-arrow-right"></i>
                    </div>

                </article>
            </a>


            @auth

                <a
                    href="{{ route('career.resume.index') }}"
                    class="feature-link"
                >

            @else

                <a
                    href="{{ route('login') }}"
                    class="feature-link"
                >

            @endauth

                <article class="feature">

                    <div class="feature-icon">
                        <i class="fas fa-compass"></i>
                    </div>

                    <h3>
                        Career Development
                    </h3>

                    <p>
                        Strengthen career readiness,
                        interview skills and workplace preparation.
                    </p>

                    <div class="feature-arrow">
                        Build Your Career
                        <i class="fas fa-arrow-right"></i>
                    </div>

                </article>

            </a>


            @auth

                <a
                    href="{{ route('career.resume.index') }}"
                    class="feature-link"
                >

            @else

                <a
                    href="{{ route('login') }}"
                    class="feature-link"
                >

            @endauth

                <article class="feature">

                    <div class="feature-icon">
                        <i class="fas fa-file-lines"></i>
                    </div>

                    <h3>
                        Resume Builder
                    </h3>

                    <p>
                        Build and manage professional resumes
                        for job and career opportunities.
                    </p>

                    <div class="feature-arrow">
                        Build Resume
                        <i class="fas fa-arrow-right"></i>
                    </div>

                </article>

            </a>


            @auth

                <a
                    href="{{ route('dashboard') }}"
                    class="feature-link"
                >

            @else

                <a
                    href="{{ route('login') }}"
                    class="feature-link"
                >

            @endauth

                <article class="feature">

                    <div class="feature-icon">
                        <i class="fas fa-calendar-days"></i>
                    </div>

                    <h3>
                        Calendar
                    </h3>

                    <p>
                        Keep training, mentorship,
                        deadlines and interview dates organised.
                    </p>

                    <div class="feature-arrow">
                        View Calendar
                        <i class="fas fa-arrow-right"></i>
                    </div>

                </article>

            </a>


            @auth

                <a
                    href="{{ route('dashboard') }}"
                    class="feature-link"
                >

            @else

                <a
                    href="{{ route('login') }}"
                    class="feature-link"
                >

            @endauth

                <article class="feature">

                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>

                    <h3>
                        Your Progress
                    </h3>

                    <p>
                        Follow achievements across learning,
                        mentorship, jobs and employment outcomes.
                    </p>

                    <div class="feature-arrow">
                        View Progress
                        <i class="fas fa-arrow-right"></i>
                    </div>

                </article>

            </a>

        </div>

    </div>

</section>


<section
    class="section full-width-section impact"
    id="impact"
>

    <div class="container impact-grid">

        <div>

            <div class="eyebrow text-secondary">
                Designed for measurable impact
            </div>

            <h2>
                Connecting programme delivery
                to participant outcomes.
            </h2>

            <p>
                ElevateHer360 provides a connected view from
                participant registration and training through
                mentorship, career development, employment and
                self-employment outcomes.
            </p>

        </div>


        <div class="stats-grid">

            <div class="stat-card gold">

                <div class="stat-content">

                    <div class="stat-label">
                        Identity
                    </div>

                    <div class="stat-value">
                        One
                    </div>

                    <div class="stat-note">
                        One participant profile across the platform
                    </div>

                </div>

                <div class="stat-icon">
                    <i class="fas fa-user"></i>
                </div>

            </div>


            <div class="stat-card gold">

                <div class="stat-content">

                    <div class="stat-label">
                        Journey
                    </div>

                    <div class="stat-value">
                        360°
                    </div>

                    <div class="stat-note">
                        Connected learning-to-opportunity journey
                    </div>

                </div>

                <div class="stat-icon">
                    <i class="fas fa-arrows-rotate"></i>
                </div>

            </div>


            <div class="stat-card gold">

                <div class="stat-content">

                    <div class="stat-label">
                        Access
                    </div>

                    <div class="stat-value">
                        RBAC
                    </div>

                    <div class="stat-note">
                        Secure role-based access control
                    </div>

                </div>

                <div class="stat-icon">
                    <i class="fas fa-shield-halved"></i>
                </div>

            </div>


            <div class="stat-card gold">

                <div class="stat-content">

                    <div class="stat-label">
                        Results
                    </div>

                    <div class="stat-value">
                        M&E
                    </div>

                    <div class="stat-note">
                        Longitudinal outcome tracking
                    </div>

                </div>

                <div class="stat-icon">
                    <i class="fas fa-chart-column"></i>
                </div>

            </div>

        </div>

    </div>

</section>


<section class="section full-width-section section-white">

    <div class="container">

        <div class="cta-box">

            <div>

                <div class="eyebrow text-secondary">
                    Take the next step
                </div>

                <h2>
                    Ready to begin your ElevateHer360 journey?
                </h2>

                <p>
                    Create one participant account for learning,
                    mentorship, careers, jobs and digital resources.
                </p>

            </div>


            @guest

                <div class="d-flex gap-2 flex-wrap">

                    <a
                        href="{{ route('register') }}"
                        class="btn btn-gold btn-lg"
                    >
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </a>

                    <a
                        href="{{ route('login') }}"
                        class="btn btn-outline btn-lg"
                    >
                        <i class="fas fa-right-to-bracket"></i>
                        Sign In
                    </a>

                </div>

            @else

                @if(auth()->user()->isStaff())

                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="btn btn-gold btn-lg"
                    >
                        <i class="fas fa-gauge-high"></i>
                        Open Dashboard
                    </a>

                @else

                    <a
                        href="{{ route('dashboard') }}"
                        class="btn btn-gold btn-lg"
                    >
                        <i class="fas fa-gauge-high"></i>
                        Open Dashboard
                    </a>

                @endif

            @endguest

        </div>

    </div>

</section>

@endsection