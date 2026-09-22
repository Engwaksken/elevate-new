<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
          content="ElevateHer360 - Learning, mentorship, career development, jobs and digital resources in one platform.">

    <title>ElevateHer360</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
            background: #ffffff;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: min(1180px, calc(100% - 32px));
            margin: auto;
        }

        .topbar {
            background: #4c1d95;
            color: white;
            padding: 8px 0;
            font-size: 14px;
        }

        .topbar .container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .navbar {
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: bold;
            font-size: 20px;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: linear-gradient(
                135deg,
                #6d28d9,
                #f59e0b
            );
            font-size: 14px;
        }

        .brand small {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-top: 2px;
        }

        nav {
            display: flex;
            gap: 24px;
            font-weight: 600;
        }

        nav a:hover {
            color: #6d28d9;
        }

        .buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 44px;
            padding: 10px 18px;
            border-radius: 9px;
            font-weight: 700;
            border: 1px solid #d1d5db;
        }

        .btn-primary {
            background: #6d28d9;
            border-color: #6d28d9;
            color: white;
        }

        .btn-primary:hover {
            background: #4c1d95;
        }

        .hero {
            padding: 85px 0;
            background: linear-gradient(
                180deg,
                #f5f3ff,
                #ffffff
            );
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 55px;
            align-items: center;
        }

        .eyebrow {
            color: #6d28d9;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        h1 {
            font-size: clamp(42px, 6vw, 70px);
            line-height: 1.04;
            margin: 14px 0 20px;
            letter-spacing: -2px;
        }

        .lead {
            color: #4b5563;
            font-size: 19px;
            line-height: 1.7;
            max-width: 720px;
        }

        .hero-actions {
            margin-top: 30px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .journey-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 25px 70px rgba(76, 29, 149, .12);
        }

        .journey-card h2 {
            margin: 8px 0 20px;
        }

        .journey-item {
            display: flex;
            gap: 14px;
            align-items: center;
            background: #f9fafb;
            padding: 14px;
            border-radius: 12px;
            margin-top: 12px;
        }

        .number {
            flex: 0 0 36px;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #ede9fe;
            color: #6d28d9;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        section {
            padding: 75px 0;
        }

        .section-title {
            max-width: 720px;
            margin-bottom: 32px;
        }

        .section-title h2 {
            font-size: 42px;
            margin: 8px 0;
        }

        .section-title p {
            color: #6b7280;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .feature {
            border: 1px solid #e5e7eb;
            border-left: 4px solid #6d28d9;
            border-radius: 13px;
            padding: 22px;
            background: white;
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            background: #f3f0ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .feature h3 {
            margin: 14px 0 8px;
        }

        .feature p {
            color: #6b7280;
            line-height: 1.6;
            margin: 0;
            font-size: 14px;
        }

        .impact {
            background: #111827;
            color: white;
        }

        .impact-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 45px;
            align-items: center;
        }

        .impact h2 {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .impact p {
            color: #d1d5db;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .stat {
            padding: 22px;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 13px;
        }

        .stat strong {
            display: block;
            color: #fbbf24;
            font-size: 26px;
        }

        .cta-box {
            border-radius: 20px;
            padding: 42px;
            background: linear-gradient(
                135deg,
                #4c1d95,
                #7c3aed
            );
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .cta-box h2 {
            margin: 0 0 8px;
            font-size: 32px;
        }

        .cta-box p {
            color: #ede9fe;
            margin: 0;
        }

        .cta-box .btn {
            background: white;
            color: #4c1d95;
            border-color: white;
        }

        footer {
            border-top: 1px solid #e5e7eb;
            padding: 30px 0;
            color: #6b7280;
            font-size: 14px;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        @media (max-width: 950px) {

            nav {
                display: none;
            }

            .hero-grid,
            .impact-grid {
                grid-template-columns: 1fr;
            }

            .features {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 620px) {

            .topbar {
                display: none;
            }

            .brand small {
                display: none;
            }

            .navbar > .buttons .btn:first-child {
                display: none;
            }

            .hero {
                padding: 50px 0;
            }

            h1 {
                font-size: 42px;
            }

            .features,
            .stats {
                grid-template-columns: 1fr;
            }

            .cta-box {
                flex-direction: column;
                align-items: flex-start;
                padding: 28px;
            }
        }
    </style>
</head>

<body>

<div class="topbar">
    <div class="container">
        <span>Women in Technology Uganda</span>
        <span>
            Learning � Mentorship � Career � Jobs � Library
        </span>
    </div>
</div>

<header>
    <div class="container navbar">

        <a href="{{ route('home') }}" class="brand">
            <span class="brand-icon">E360</span>

            <span>
                ElevateHer360
                <small>One journey. One platform.</small>
            </span>
        </a>

        <nav>
            <a href="#platform">Platform</a>
            <a href="#journey">Participant Journey</a>
            <a href="#impact">Impact</a>
        </nav>

        <div class="buttons">

            @auth

                <a href="{{ route('dashboard') }}"
                   class="btn btn-primary">
                    Dashboard
                </a>

            @else

                <a href="{{ route('login') }}"
                   class="btn">
                    Sign In
                </a>

                <a href="{{ route('register') }}"
                   class="btn btn-primary">
                    Register
                </a>

            @endauth

        </div>

    </div>
</header>

<main>

    <section class="hero">

        <div class="container hero-grid">

            <div>

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

                        <a href="{{ route('dashboard') }}"
                           class="btn btn-primary">
                            Continue to Dashboard
                        </a>

                    @else

                        <a href="{{ route('register') }}"
                           class="btn btn-primary">
                            Create Participant Account
                        </a>

                        <a href="{{ route('login') }}"
                           class="btn">
                            Participant Sign In
                        </a>

                    @endauth

                    <a href="{{ route('admin.login') }}"
                       class="btn">
                        Staff Sign In
                    </a>

                </div>

            </div>

            <div class="journey-card" id="journey">

                <div class="eyebrow">
                    One connected journey
                </div>

                <h2>
                    From registration to outcomes
                </h2>

                <div class="journey-item">

                    <span class="number">1</span>

                    <div>
                        <strong>Learn</strong><br>
                        Courses, assessments and certificates
                    </div>

                </div>

                <div class="journey-item">

                    <span class="number">2</span>

                    <div>
                        <strong>Connect</strong><br>
                        Mentors, goals and structured sessions
                    </div>

                </div>

                <div class="journey-item">

                    <span class="number">3</span>

                    <div>
                        <strong>Prepare</strong><br>
                        Career coaching and resume building
                    </div>

                </div>

                <div class="journey-item">

                    <span class="number">4</span>

                    <div>
                        <strong>Progress</strong><br>
                        Job applications and outcome tracking
                    </div>

                </div>

            </div>

        </div>

    </section>


    <section id="platform">

        <div class="container">

            <div class="section-title">

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

                <div class="feature">
                    <div class="feature-icon">??</div>
                    <h3>Learning</h3>
                    <p>
                        Courses, lessons, assessments, attendance,
                        progress tracking and certificates.
                    </p>
                </div>

                <div class="feature">
                    <div class="feature-icon">??</div>
                    <h3>Mentorship</h3>
                    <p>
                        Mentor matching, sessions, goals,
                        milestones and action plans.
                    </p>
                </div>

                <div class="feature">
                    <div class="feature-icon">??</div>
                    <h3>Jobs</h3>
                    <p>
                        Find opportunities, save jobs, apply and
                        track your application journey.
                    </p>
                </div>

                <div class="feature">
                    <div class="feature-icon">??</div>
                    <h3>Digital Library</h3>
                    <p>
                        Access curated learning and professional
                        development resources.
                    </p>
                </div>

                <div class="feature">
                    <div class="feature-icon">??</div>
                    <h3>Career Development</h3>
                    <p>
                        Career navigation, interview readiness
                        and workplace preparation.
                    </p>
                </div>

                <div class="feature">
                    <div class="feature-icon">??</div>
                    <h3>Resume Builder</h3>
                    <p>
                        Build multiple professional resumes and
                        use them in job applications.
                    </p>
                </div>

                <div class="feature">
                    <div class="feature-icon">??</div>
                    <h3>Calendar</h3>
                    <p>
                        Keep training, mentorship and interview
                        dates together.
                    </p>
                </div>

                <div class="feature">
                    <div class="feature-icon">??</div>
                    <h3>Your Progress</h3>
                    <p>
                        Follow achievements across learning,
                        mentorship and employment.
                    </p>
                </div>

            </div>

        </div>

    </section>


    <section class="impact" id="impact">

        <div class="container impact-grid">

            <div>

                <div class="eyebrow"
                     style="color:#fbbf24">
                    Designed for measurable impact
                </div>

                <h2>
                    Connecting programme delivery
                    to participant outcomes.
                </h2>

                <p>
                    ElevateHer360 provides a longitudinal view from
                    participant registration and training through
                    mentorship, employment and self-employment
                    outcomes.
                </p>

            </div>

            <div class="stats">

                <div class="stat">
                    <strong>1</strong>
                    Participant Identity
                </div>

                <div class="stat">
                    <strong>360�</strong>
                    Journey Visibility
                </div>

                <div class="stat">
                    <strong>RBAC</strong>
                    Role-Based Access
                </div>

                <div class="stat">
                    <strong>M&E</strong>
                    Outcome Tracking
                </div>

            </div>

        </div>

    </section>


    <section>

        <div class="container">

            <div class="cta-box">

                <div>

                    <h2>
                        Ready to begin your journey?
                    </h2>

                    <p>
                        Create one account for learning,
                        mentorship, careers, jobs and resources.
                    </p>

                </div>

                @guest

                    <div class="buttons">

                        <a href="{{ route('register') }}"
                           class="btn">
                            Register
                        </a>

                        <a href="{{ route('login') }}"
                           class="btn">
                            Sign In
                        </a>

                    </div>

                @else

                    <a href="{{ route('dashboard') }}"
                       class="btn">
                        Open Dashboard
                    </a>

                @endguest

            </div>

        </div>

    </section>

</main>

<footer>

    <div class="container footer">

        <span>
            � {{ date('Y') }}
            ElevateHer360 � Women in Technology Uganda
        </span>

        <a href="{{ route('admin.login') }}">
            Staff Portal
        </a>

    </div>

</footer>

</body>
</html>