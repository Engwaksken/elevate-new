{{-- Draft notice goes into <head>: an HTML comment before <!DOCTYPE> would put browsers in quirks mode. --}}
@push('head')
<!--
    DRAFT - REQUIRES LEGAL REVIEW.
    This Privacy Policy was drafted from what the ElevateHer360 code actually
    collects and does (Laravel platform and the participant mobile app) as of
    30 September 2026. It is not legal advice. Have it reviewed by qualified
    counsel before relying on it, and fill in every highlighted placeholder
    via the LEGAL_* keys in .env (see config/legal.php).
-->
@endpush

@extends('layouts.app')

@section('title', 'Privacy Policy | ElevateHer360')

@section('meta_description', 'How ElevateHer360 collects, uses, shares, protects and deletes personal data on the website and the participant mobile app.')

@php
    $placeholder = fn (string $label) => new \Illuminate\Support\HtmlString('<span class="legal-placeholder">['.e($label).']</span>');
    $org = filled(config('legal.organisation_name')) ? config('legal.organisation_name') : $placeholder('Organisation legal name to be confirmed');
    $address = filled(config('legal.address')) ? config('legal.address') : $placeholder('Postal address to be confirmed');
    $jurisdiction = filled(config('legal.jurisdiction')) ? config('legal.jurisdiction') : $placeholder('Country / applicable data protection law to be confirmed');
    $registration = config('legal.data_protection_registration');
    $email = config('legal.support_email') ?: 'support@elevateher360.org';
    $minAge = (int) config('legal.minimum_age', 18);
    $deletionDays = (int) config('legal.deletion_response_days', 30);

    $sections = [
        'who-we-are' => 'Who we are',
        'scope' => 'What this policy covers',
        'data-we-collect' => 'Data we collect',
        'on-your-device' => 'Data stored on your device',
        'how-we-use' => 'Why we use your data',
        'legal-basis' => 'Legal basis and consent',
        'sharing' => 'Who we share data with',
        'transfers' => 'International transfers',
        'retention' => 'How long we keep data',
        'security' => 'How we protect data',
        'your-rights' => 'Your rights',
        'deletion' => 'Deleting your account and data',
        'cookies' => 'Cookies and browser storage',
        'children' => 'Children',
        'changes' => 'Changes to this policy',
        'contact' => 'Contact us',
    ];
@endphp

@section('content')
<article class="legal-page">

    @include('legal.partials.header', [
        'heading' => 'Privacy Policy',
        'intro' => 'This policy explains what personal data ElevateHer360 collects through its website and participant mobile app, why we collect it, who we share it with, how long we keep it, and the choices and rights you have.',
    ])

    <nav class="card legal-toc" aria-label="Contents">
        <h2>Contents</h2>
        <ol>
            @foreach($sections as $id => $label)
                <li><a href="#{{ $id }}">{{ $label }}</a></li>
            @endforeach
        </ol>
    </nav>

    <div class="card legal-body">

        <section id="who-we-are">
            <h2>1. Who we are</h2>
            <p>ElevateHer360 is a learning, mentorship, career development and employment platform. It is operated by {{ $org }} ("we", "us", "our"), which is responsible for your personal data as the data controller.</p>
            <p>Address: {{ $address }}</p>
            @if(filled($registration))
                <p>Data protection registration number: {{ $registration }}</p>
            @endif
            <p>Privacy contact: <a href="mailto:{{ $email }}">{{ $email }}</a></p>
        </section>

        <section id="scope">
            <h2>2. What this policy covers</h2>
            <p>This policy applies to the ElevateHer360 website (including participant, mentor, employer and instructor areas) and the ElevateHer360 participant mobile app for Android and iOS. The app uses the same account and the same servers as the website.</p>
        </section>

        <section id="data-we-collect">
            <h2>3. Data we collect</h2>

            <h3>Account and sign-in details</h3>
            <ul>
                <li>Your name, email address, phone number (optional) and password. Passwords are stored only as a one-way hash; we cannot read them.</li>
                <li>Account type, status (for example pending approval or active), email verification status and when you last signed in.</li>
                <li>When you sign in to the app, a sign-in token is created for your device and labelled with the device's operating system (for example "ElevateHer360 android").</li>
            </ul>

            <h3>Profile details you provide at registration or later</h3>
            <ul>
                <li>Surname, given name and other names; the branch or centre you are linked to.</li>
                <li>Gender, date of birth, country, district and location.</li>
                <li>Whether you are a person with a disability and, if you choose to tell us, the type of disability. This is sensitive information: it is optional and is used to provide accessibility support and to report on inclusion.</li>
                <li>Education level, employment status, career interests and preferred language.</li>
            </ul>

            <h3>Learning activity</h3>
            <ul>
                <li>Course applications and your answers to application questions; enrolments and cohorts.</li>
                <li>Lesson progress and completion, assessment and quiz attempts, answers and scores.</li>
                <li>Assignment submissions: the text you write and any file you choose to attach (for example documents, spreadsheets, presentations, images or ZIP files, up to 50 MB). In the app, files are attached only through the file picker, the camera or your photo library, and only when you choose to do so. The app does not read your storage in the background.</li>
                <li>Grades and feedback from instructors, attendance records and certificates issued to you.</li>
            </ul>

            <h3>Career, jobs and mentorship</h3>
            <ul>
                <li>Resumes you build or upload (including text extracted from uploaded files), cover letters, and the details they contain.</li>
                <li>Jobs you save, job applications you submit (including any cover letter), and interview or offer records linked to them.</li>
                <li>Mentee and mentor profiles (for example career goals, skills, support needs, preferred mentoring areas and availability), mentor matches, mentoring sessions and goals.</li>
            </ul>

            <h3>Events, surveys and communication</h3>
            <ul>
                <li>Event registrations, check-in and attendance, event feedback and event certificates.</li>
                <li>Survey responses you submit.</li>
                <li>In-platform notifications and announcements sent to you, and whether you have read them.</li>
            </ul>

            <h3>Device and push-notification data (mobile app)</h3>
            <ul>
                <li>A random device identifier generated by the app (it is not your phone's hardware ID), your Firebase Cloud Messaging (FCM) push token, the platform (Android or iOS), the app version, whether notifications are enabled and when the device was last active.</li>
                <li>Records of actions the app queued while you were offline (for example lesson progress or a submission) so that they can be synchronised once and only once.</li>
            </ul>

            <h3>Security and technical records</h3>
            <ul>
                <li>Sign-in records, including the email address used, IP address, browser or device type (user agent), the time, and the reason a sign-in failed.</li>
                <li>Audit records of important actions (for example changes to records), with your IP address and user agent.</li>
                <li>Records of the consent you gave to this policy and the Terms of Use: the version, the time and the IP address.</li>
                <li>Where optional AI-assisted career tools are used, a usage record of which feature was used, the AI provider and model, and whether the request succeeded.</li>
            </ul>

            <p>We do not use advertising identifiers, we do not use third-party analytics or advertising tools in the app, and the app does not access your location or contacts.</p>
        </section>

        <section id="on-your-device">
            <h2>4. Data stored on your device</h2>
            <p>So that the app keeps working with a weak or no internet connection, it stores some data on your phone:</p>
            <ul>
                <li><strong>Secure storage</strong> (Android Keystore or iOS Keychain): your sign-in token and a copy of your basic account details.</li>
                <li><strong>A local database</strong>: cached copies of your courses, lessons, assignments, jobs, mentorship, events, announcements and notifications, and any actions waiting to be sent to our servers.</li>
                <li><strong>Downloaded files</strong>: lesson materials you choose to download for offline use.</li>
                <li><strong>App settings</strong>: preferences such as downloading only on Wi-Fi, and the app's random device identifier.</li>
            </ul>
            <p>When you sign out, the app removes your sign-in token, cached data, queued actions and downloaded files from the device and asks our servers to stop sending push notifications to it. Uninstalling the app also removes this data.</p>
        </section>

        <section id="how-we-use">
            <h2>5. Why we use your data</h2>
            <ul>
                <li><strong>To create and run your account</strong>, verify who you are and keep you signed in.</li>
                <li><strong>To deliver learning</strong>: enrol you in courses, track progress, receive and grade submissions, give feedback and issue certificates.</li>
                <li><strong>To support your career</strong>: build resumes and cover letters, show and recommend jobs, and send your applications to the employers you apply to.</li>
                <li><strong>To run mentorship</strong>: match you with mentors and organise sessions and goals.</li>
                <li><strong>To communicate with you</strong>: in-app notifications, push notifications and emails about your account, courses, deadlines, events and opportunities.</li>
                <li><strong>To make the platform accessible</strong> and to provide support where you have told us you have a disability.</li>
                <li><strong>To keep the platform secure</strong>: detect and prevent unauthorised access, misuse and fraud, and keep audit trails.</li>
                <li><strong>To measure and improve our programmes</strong>: monitoring and evaluation of participation and outcomes, using aggregated or de-identified figures wherever possible.</li>
                <li><strong>To meet legal obligations</strong> and respond to lawful requests.</li>
            </ul>
        </section>

        <section id="legal-basis">
            <h2>6. Legal basis and consent</h2>
            <p>We rely on the following grounds, as permitted under the data protection law that applies to us ({{ $jurisdiction }}):</p>
            <ul>
                <li><strong>Consent.</strong> When you register you are asked to agree to this Privacy Policy and the Terms of Use, and we record that consent. Optional information, including sensitive information such as disability status, is processed on the basis of your consent. You can withdraw consent at any time (see <a href="#your-rights">Your rights</a>); this does not affect processing that took place before you withdrew it.</li>
                <li><strong>Providing the service you asked for.</strong> Most account, learning, career and mentorship data is needed to provide the programme and platform you signed up for.</li>
                <li><strong>Legitimate interests.</strong> Security, preventing misuse, and improving and evaluating our programmes, balanced against your rights.</li>
                <li><strong>Legal obligation.</strong> Where the law requires us to keep or disclose information.</li>
            </ul>
            <p>Push notifications are only shown if you allow them on your device. You can turn them off at any time in your phone's settings.</p>
        </section>

        <section id="sharing">
            <h2>7. Who we share data with</h2>
            <p>We do not sell your personal data and we do not share it for advertising. We share it only as follows:</p>

            <div class="legal-table-wrap">
                <table class="legal-table">
                    <thead>
                        <tr><th scope="col">Recipient</th><th scope="col">What is shared</th><th scope="col">Why</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Our staff, instructors and mentors</td>
                            <td>Only the data their role needs (for example an instructor sees submissions for their course; a mentor sees their mentees' mentorship details)</td>
                            <td>To run the programme. Access is controlled by roles and permissions.</td>
                        </tr>
                        <tr>
                            <td>Employers on the platform</td>
                            <td>Your application, resume, cover letter and the profile details included in it, when you apply to their job</td>
                            <td>So they can consider your application. Employers are responsible for how they use it after they receive it.</td>
                        </tr>
                        <tr>
                            <td>Google (Firebase Cloud Messaging) and, on iPhone, Apple Push Notification service</td>
                            <td>Your push token, app instance identifiers and the content of notifications sent to you</td>
                            <td>To deliver push notifications to your device</td>
                        </tr>
                        <tr>
                            <td>AI service providers (OpenAI or Google Gemini), only if we have enabled AI career tools</td>
                            <td>The resume content and job details you submit when you use resume improvement, resume screening checks or cover letter generation</td>
                            <td>To generate the suggestions you asked for. Nothing is sent unless you use one of these tools.</td>
                        </tr>
                        <tr>
                            <td>Our email delivery provider</td>
                            <td>Your name, email address and the message</td>
                            <td>To send account, security and programme emails</td>
                        </tr>
                        <tr>
                            <td>Our hosting and infrastructure providers</td>
                            <td>All platform data, stored on our behalf</td>
                            <td>To host the website, app servers, database and uploaded files</td>
                        </tr>
                        <tr>
                            <td>Authorities, courts or advisers</td>
                            <td>Only what is required</td>
                            <td>Where required by law, or to protect the rights, safety and security of users and the platform</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p>Where we report programme results to partners or funders, we use aggregated or de-identified information unless you have agreed otherwise.</p>
        </section>

        <section id="transfers">
            <h2>8. International transfers</h2>
            <p>Some of the providers above, such as Google, Apple and AI providers, may process data on servers outside your country. Where this happens we rely on the safeguards those providers offer and on the transfer rules of the applicable data protection law.</p>
        </section>

        <section id="retention">
            <h2>9. How long we keep data</h2>
            <ul>
                <li><strong>Account, profile, learning, career and mentorship data</strong> is kept while your account is active, and deleted or anonymised when your account is deleted (see <a href="#deletion">Deleting your account and data</a>).</li>
                <li><strong>Push tokens</strong> are removed when you sign out of the app or your account is deleted, and replaced when Firebase issues a new token.</li>
                <li><strong>Data on your device</strong> is kept until you sign out or uninstall the app.</li>
                <li><strong>Security, audit and consent records</strong> may be kept for longer where needed to protect the platform, resolve disputes or meet legal obligations. When an account is deleted these records are de-identified where possible.</li>
                <li><strong>Aggregated or de-identified statistics</strong> that no longer identify you may be kept for programme reporting.</li>
            </ul>
        </section>

        <section id="security">
            <h2>10. How we protect data</h2>
            <ul>
                <li>Data between the app or browser and our servers is sent over encrypted connections (HTTPS).</li>
                <li>Passwords are hashed; the app keeps its sign-in token in the device's secure storage (Android Keystore / iOS Keychain).</li>
                <li>Access to data inside the platform is limited by roles and permissions, and important actions are logged.</li>
                <li>Uploaded files, such as assignment submissions and CVs, are kept in private storage and are only available to signed-in users who are allowed to see them.</li>
            </ul>
            <p>No system is completely secure. If you think your account has been compromised, change your password and contact us straight away at <a href="mailto:{{ $email }}">{{ $email }}</a>.</p>
        </section>

        <section id="your-rights">
            <h2>11. Your rights</h2>
            <p>Subject to the applicable law, you have the right to:</p>
            <ul>
                <li><strong>Access</strong> the personal data we hold about you and receive a copy.</li>
                <li><strong>Correct</strong> data that is inaccurate or incomplete. You can update many profile details yourself in your account; for anything else, contact us.</li>
                <li><strong>Delete</strong> your account and personal data (see the next section).</li>
                <li><strong>Withdraw consent</strong> for processing that relies on consent, including optional sensitive information.</li>
                <li><strong>Object to or restrict</strong> certain processing, such as processing based on our legitimate interests.</li>
                <li><strong>Turn off push notifications</strong> at any time in your device settings.</li>
                <li><strong>Complain</strong> to the data protection authority responsible under {{ $jurisdiction }}.</li>
            </ul>
            <p>To use any of these rights, email <a href="mailto:{{ $email }}">{{ $email }}</a> from the email address linked to your account. We may ask you to confirm your identity before acting on the request.</p>
        </section>

        <section id="deletion">
            <h2>12. Deleting your account and data</h2>
            <div class="legal-callout">
                <p><strong>How to request deletion:</strong> send an email to <a href="mailto:{{ $email }}?subject=Account%20deletion%20request">{{ $email }}</a> with the subject "Account deletion request", from the email address registered to your ElevateHer360 account. Include your full name and say whether you want your whole account deleted or only certain data.</p>
                <p>You do not need to have the app installed to request deletion. We will confirm your identity, then complete the request within {{ $deletionDays }} days and confirm by email when it is done.</p>
            </div>

            <h3>What is deleted</h3>
            <ul>
                <li>Your account, sign-in details and app sign-in tokens.</li>
                <li>Your profile, including gender, date of birth, location, disability information, education, employment and career interests.</li>
                <li>Push notification tokens and device records.</li>
                <li>Lesson progress, assessment attempts and answers, assignment submissions and the files you uploaded.</li>
                <li>Resumes, uploaded CVs, cover letters, saved jobs and job applications.</li>
                <li>Mentee or mentor profiles, mentorship goals and sessions.</li>
                <li>Event registrations, survey responses and notifications addressed to you.</li>
            </ul>

            <h3>What may be kept</h3>
            <ul>
                <li>Records we must keep to meet a legal obligation or to establish or defend legal claims, kept only as long as necessary.</li>
                <li>Security and audit logs and attendance records, which are de-identified so they are no longer linked to you.</li>
                <li>Aggregated statistics that do not identify you.</li>
                <li>Copies of an application you already sent to an employer, which that employer holds. Contact the employer directly to have those deleted.</li>
            </ul>
            <p>Deletion is permanent. After deletion you will no longer be able to sign in, and certificates issued to you may no longer be verifiable through the platform, so download anything you want to keep first.</p>
            <p>Signing out of the app or uninstalling it deletes data stored on your device, but does not delete your account on our servers. To delete your account, send the request above.</p>
        </section>

        <section id="cookies">
            <h2>13. Cookies and browser storage</h2>
            <p>The website uses cookies that are needed for it to work: a session cookie that keeps you signed in and a security token that protects forms against forgery. It also saves your accessibility preferences (for example text size or high contrast) in your browser's local storage. We do not use advertising or third-party analytics cookies.</p>
        </section>

        <section id="children">
            <h2>14. Children</h2>
            <p>ElevateHer360 is not intended for children under {{ $minAge }}. People under {{ $minAge }} should not register or share personal data with us unless a parent or legal guardian has given consent where the law requires it. If we learn that we have collected personal data from a child without the consent required by law, we will delete it. If you believe this has happened, contact <a href="mailto:{{ $email }}">{{ $email }}</a>.</p>
        </section>

        <section id="changes">
            <h2>15. Changes to this policy</h2>
            <p>We may update this policy when the platform or the law changes. The "Last updated" date at the top shows when it last changed. If we make significant changes we will tell you through the platform, the app or by email, and where the law requires it we will ask for your consent again.</p>
        </section>

        <section id="contact">
            <h2>16. Contact us</h2>
            <p>For questions about this policy or your personal data, or to use your rights:</p>
            <ul>
                <li>Email: <a href="mailto:{{ $email }}">{{ $email }}</a></li>
                <li>Organisation: {{ $org }}</li>
                <li>Address: {{ $address }}</li>
            </ul>
            <p>Please also read our <a href="{{ route('legal.terms') }}">Terms of Use</a>.</p>
        </section>

    </div>
</article>
@endsection
