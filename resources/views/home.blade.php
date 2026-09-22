@extends('layouts.app')

@section('title', 'ElevateHer360 | Learn, Connect and Grow')

@section(
    'meta_description',
    'ElevateHer360 brings learning, mentorship, career development, job opportunities and digital resources together in one platform.'
)

@section('content')

<section class="hero">

    <div class="hero-grid">

        <div>

            <div class="eyebrow">
                Your complete growth journey
            </div>

            <h1>
                Learn, connect, build your career
                and move into opportunity.
            </h1>

            <p class="lead">

                ElevateHer360 brings training,
                mentorship, career development,
                employment opportunities and digital
                resources together under one
                participant account.

            </p>

            <div class="hero-actions">

                @auth

                    @if(auth()->user()->isStaff())

                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="btn btn-primary btn-lg"
                        >
                            Open Staff Dashboard
                        </a>

                    @else

                        <a
                            href="{{ route('dashboard') }}"
                            class="btn btn-primary btn-lg"
                        >
                            Continue to Dashboard
                        </a>

                    @endif

                @else

                    <a
                        href="{{ route('register') }}"
                        class="btn btn-primary btn-lg"
                    >
                        Create Participant Account
                    </a>

                    <a
                        href="{{ route('login') }}"
                        class="btn btn-outline btn-lg"
                    >
                        Participant Sign In
                    </a>

                    <a
                        href="{{ route('admin.login') }}"
                        class="btn btn-gold btn-lg"
                    >
                        Staff Sign In
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

                <span class="number">
                    1
                </span>

                <div>
                    <strong>Learn</strong>

                    <br>

                    Courses, assessments,
                    attendance and certificates
                </div>

            </div>

            <div class="journey-item">

                <span class="number">
                    2
                </span>

                <div>
                    <strong>Connect</strong>

                    <br>

                    Mentors, goals,
                    sessions and milestones
                </div>

            </div>

            <div class="journey-item">

                <span class="number">
                    3
                </span>

                <div>
                    <strong>Prepare</strong>

                    <br>

                    Career coaching,
                    skills and resume building
                </div>

            </div>

            <div class="journey-item">

                <span class="number">
                    4
                </span>

                <div>
                    <strong>Progress</strong>

                    <br>

                    Job applications,
                    employment and outcome tracking
                </div>

            </div>

        </div>

    </div>

</section>


<section class="section" id="platform">

    <div class="section-header">

        <div class="eyebrow">
            Everything in one place
        </div>

        <h2>
            One participant. One platform.
        </h2>

        <p>
            Your learning, mentorship, career and
            employment journey stays connected to
            one profile.
        </p>

    </div>

    <div class="features">

        <article class="feature">

            <div class="feature-icon">
                L
            </div>

            <h3>
                Learning
            </h3>

            <p>
                Courses, lessons, assessments,
                attendance, progress tracking and
                certificates.
            </p>

        </article>

        <article class="feature">

            <div class="feature-icon">
                M
            </div>

            <h3>
                Mentorship
            </h3>

            <p>
                Mentor matching, sessions,
                goals, milestones and action plans.
            </p>

        </article>

        <article class="feature">

            <div class="feature-icon">
                J
            </div>

            <h3>
                Jobs
            </h3>

            <p>
                Discover opportunities,
                save jobs, apply and track your
                application journey.
            </p>

        </article>

        <article class="feature">

            <div class="feature-icon">
                EL
            </div>

            <h3>
                Digital Library
            </h3>

            <p>
                Access curated learning and
                professional development resources.
            </p>

        </article>

        <article class="feature">

            <div class="feature-icon">
                C
            </div>

            <h3>
                Career Development
            </h3>

            <p>
                Career navigation,
                interview readiness and workplace
                preparation.
            </p>

        </article>

        <article class="feature">

            <div class="feature-icon">
                CV
            </div>

            <h3>
                Resume Builder
            </h3>

            <p>
                Build professional resumes and
                use them directly in job
                applications.
            </p>

        </article>

        <article class="feature">

            <div class="feature-icon">
                CAL
            </div>

            <h3>
                Calendar
            </h3>

            <p>
                Keep training, mentorship,
                deadlines and interview dates
                together.
            </p>

        </article>

        <article class="feature">

            <div class="feature-icon">
                %
            </div>

            <h3>
                Progress
            </h3>

            <p>
                Follow achievements across
                learning, mentorship, jobs and
                employment outcomes.
            </p>

        </article>

    </div>

</section>


<section class="section impact" id="impact">

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
                ElevateHer360 provides a longitudinal
                view from participant registration
                and training through mentorship,
                employment and self-employment.
            </p>

        </div>

        <div class="grid-2">

            <div class="stat-card gold">

                <div class="stat-content">

                    <div class="stat-label">
                        Identity
                    </div>

                    <div class="stat-value">
                        One
                    </div>

                    <div class="stat-note">
                        One participant profile
                    </div>

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
                        Connected programme journey
                    </div>

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
                        Role-based access control
                    </div>

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

            </div>

        </div>

    </div>

</section>


<section class="section">

    <div class="cta-box">

        <div>

            <h2>
                Ready to begin your journey?
            </h2>

            <p>
                Create one account for learning,
                mentorship, careers, jobs and
                digital resources.
            </p>

        </div>

        @guest

            <div class="d-flex gap-2 flex-wrap">

                <a
                    href="{{ route('register') }}"
                    class="btn btn-gold"
                >
                    Register
                </a>

                <a
                    href="{{ route('login') }}"
                    class="btn btn-outline"
                >
                    Sign In
                </a>

            </div>

        @else

            @if(auth()->user()->isStaff())

                <a
                    href="{{ route('admin.dashboard') }}"
                    class="btn btn-gold"
                >
                    Open Staff Dashboard
                </a>

            @else

                <a
                    href="{{ route('dashboard') }}"
                    class="btn btn-gold"
                >
                    Open Dashboard
                </a>

            @endif

        @endguest

    </div>

</section>

@endsection