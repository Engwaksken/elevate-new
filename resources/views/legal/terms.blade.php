{{-- Draft notice goes into <head>: an HTML comment before <!DOCTYPE> would put browsers in quirks mode. --}}
@push('head')
<!--
    DRAFT - REQUIRES LEGAL REVIEW.
    These Terms of Use were drafted from how the ElevateHer360 platform and
    participant mobile app work as of 30 September 2026. They are not legal
    advice. Have them reviewed by qualified counsel before relying on them,
    and fill in every highlighted placeholder via the LEGAL_* keys in .env
    (see config/legal.php). Governing law in particular must be confirmed.
-->
@endpush

@extends('layouts.app')

@section('title', 'Terms of Use | ElevateHer360')

@section('meta_description', 'The terms that apply when you use the ElevateHer360 website and participant mobile app.')

@php
    $placeholder = fn (string $label) => new \Illuminate\Support\HtmlString('<span class="legal-placeholder">['.e($label).']</span>');
    $org = filled(config('legal.organisation_name')) ? config('legal.organisation_name') : $placeholder('Organisation legal name to be confirmed');
    $address = filled(config('legal.address')) ? config('legal.address') : $placeholder('Postal address to be confirmed');
    $jurisdiction = filled(config('legal.jurisdiction')) ? config('legal.jurisdiction') : $placeholder('Governing law / jurisdiction to be confirmed');
    $email = config('legal.support_email') ?: 'support@elevateher360.org';
    $minAge = (int) config('legal.minimum_age', 18);

    $sections = [
        'agreement' => 'About these terms',
        'eligibility' => 'Eligibility',
        'accounts' => 'Your account',
        'acceptable-use' => 'Acceptable use',
        'content-ip' => 'Our content and intellectual property',
        'submissions' => 'Your submissions and content',
        'opportunities' => 'Jobs, employers and mentorship',
        'ai-tools' => 'AI-assisted career tools',
        'availability' => 'Availability, offline use and changes',
        'termination' => 'Suspension and termination',
        'disclaimers' => 'Disclaimers',
        'liability' => 'Limitation of liability',
        'law' => 'Governing law',
        'changes' => 'Changes to these terms',
        'contact' => 'Contact us',
    ];
@endphp

@section('content')
<article class="legal-page">

    @include('legal.partials.header', [
        'heading' => 'Terms of Use',
        'intro' => 'These terms set out the rules for using the ElevateHer360 website and participant mobile app. Please read them together with our Privacy Policy.',
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

        <section id="agreement">
            <h2>1. About these terms</h2>
            <p>ElevateHer360 is operated by {{ $org }} ("we", "us", "our"). These Terms of Use apply to the ElevateHer360 website and the participant mobile app (together, the "platform"). By creating an account or using the platform you agree to these terms and to our <a href="{{ route('legal.privacy') }}">Privacy Policy</a>. If you do not agree, do not use the platform.</p>
            <p>Some programmes, courses, events or employer services may have extra rules. If they do, we will show them to you, and they apply together with these terms.</p>
        </section>

        <section id="eligibility">
            <h2>2. Eligibility</h2>
            <ul>
                <li>You must be at least {{ $minAge }} years old, or have the consent of a parent or legal guardian where the law allows younger users.</li>
                <li>You must give accurate information when you register. New participant accounts may need to be reviewed and approved before all features are available.</li>
                <li>Places on programmes and courses may be limited and may depend on eligibility criteria set for each programme. Having an account does not guarantee a place.</li>
            </ul>
        </section>

        <section id="accounts">
            <h2>3. Your account</h2>
            <ul>
                <li>Your account is personal to you. Do not share your password or let anyone else use your account.</li>
                <li>Keep your details accurate and up to date.</li>
                <li>You are responsible for activity under your account. Tell us immediately at <a href="mailto:{{ $email }}">{{ $email }}</a> if you think someone else has used it.</li>
                <li>If you sign in on a shared or lost device, sign out of the app to remove your data from that device.</li>
                <li>You can ask us to delete your account at any time, as explained in the <a href="{{ route('legal.privacy') }}#deletion">Privacy Policy</a>.</li>
            </ul>
        </section>

        <section id="acceptable-use">
            <h2>4. Acceptable use</h2>
            <p>You must not:</p>
            <ul>
                <li>break any law, or use the platform to harass, threaten, discriminate against, exploit or harm anyone;</li>
                <li>upload content that is unlawful, abusive, sexually explicit, hateful, misleading, or that infringes someone else's rights or privacy;</li>
                <li>upload viruses or other harmful code, or try to access accounts, data or parts of the platform you are not allowed to access;</li>
                <li>interfere with the platform, overload it, or copy large parts of it by automated means;</li>
                <li>impersonate another person, create accounts with false details, or misrepresent your qualifications to employers or mentors;</li>
                <li>cheat in assessments, submit work that is not your own without saying so, or share assessment answers;</li>
                <li>collect other users' personal data, or contact participants, mentors or employers for purposes unrelated to the platform, such as spam or unrelated selling.</li>
            </ul>
        </section>

        <section id="content-ip">
            <h2>5. Our content and intellectual property</h2>
            <p>Courses, lessons, videos, documents, library resources, assessments, certificates, the ElevateHer360 name and logo, and the software behind the platform belong to us or to the people who licensed them to us.</p>
            <p>We give you a personal, non-exclusive, non-transferable permission to view and use this content for your own learning and career development while you have an account. Materials you download for offline use in the app are for your personal use only. You must not copy, sell, publish or share our content with others unless the material says you may, or we give you written permission.</p>
        </section>

        <section id="submissions">
            <h2>6. Your submissions and content</h2>
            <ul>
                <li>You keep ownership of what you create and upload, such as assignment submissions, resumes, cover letters, applications, survey answers and feedback.</li>
                <li>You allow us, and the instructors, mentors and staff involved in your programme, to store, copy, review, grade, comment on and display your content as needed to run the platform and your programme. When you apply for a job, you allow us to send your application to that employer.</li>
                <li>You confirm that your content is your own work or that you have the right to share it, and that it does not break these terms.</li>
                <li>Only upload the files needed for the task. Do not upload other people's personal data unless you have their permission.</li>
                <li>Submissions made in the app while offline are sent when your device reconnects. A submission counts as received when our servers accept it, not when you tap submit. Check deadlines and make sure your submission has synchronised.</li>
                <li>We may remove content that breaks these terms or the law.</li>
            </ul>
        </section>

        <section id="opportunities">
            <h2>7. Jobs, employers and mentorship</h2>
            <ul>
                <li>Job listings and opportunities are provided by employers and partners. We try to keep them accurate, but we do not guarantee any job, interview, offer or outcome, and we are not a party to any employment agreement between you and an employer.</li>
                <li>Check an opportunity and the employer yourself before sharing further personal information or accepting an offer. Never pay anyone to get a job. Report any suspicious listing to <a href="mailto:{{ $email }}">{{ $email }}</a>.</li>
                <li>Mentors give guidance in good faith. Their advice is not professional legal, financial, medical or similar advice. Treat mentors, mentees and staff with respect, and report any behaviour that makes you feel unsafe.</li>
            </ul>
        </section>

        <section id="ai-tools">
            <h2>8. AI-assisted career tools</h2>
            <p>Where we have enabled them, some career tools, such as resume suggestions and cover letter drafts, are produced with the help of third-party AI services. AI output can be incomplete or wrong. Review and edit anything generated before you use it, and make sure it truthfully reflects your own experience. You are responsible for what you submit to employers.</p>
        </section>

        <section id="availability">
            <h2>9. Availability, offline use and changes</h2>
            <ul>
                <li>We work to keep the platform available and secure, but we cannot promise it will always be available, uninterrupted or free of errors. We may need to pause it for maintenance, updates or security reasons.</li>
                <li>The app can show saved content offline. Offline content may be out of date until the app synchronises again.</li>
                <li>We may change, add or remove features, courses and content, and may require you to update the app to keep using it.</li>
            </ul>
        </section>

        <section id="termination">
            <h2>10. Suspension and termination</h2>
            <ul>
                <li>You can stop using the platform at any time and ask us to delete your account.</li>
                <li>We may suspend or close your account, or remove your access to a programme, if you break these terms, if we need to protect other users or the platform, or if the law requires it. Where it is reasonable, we will tell you why and give you a chance to respond.</li>
                <li>We may withdraw a certificate if it was obtained through cheating or false information.</li>
                <li>Sections of these terms that by their nature should continue, such as those on intellectual property, disclaimers and limitation of liability, continue to apply after your account ends.</li>
            </ul>
        </section>

        <section id="disclaimers">
            <h2>11. Disclaimers</h2>
            <p>The platform and its content are provided for learning and career development. As far as the law allows, they are provided "as is" and "as available", without promises that they will meet your particular needs or lead to a particular result, such as employment. Nothing in these terms limits rights you have under consumer or other law that cannot be excluded.</p>
        </section>

        <section id="liability">
            <h2>12. Limitation of liability</h2>
            <p>As far as the law allows, we are not responsible for:</p>
            <ul>
                <li>indirect or consequential loss, or loss of opportunity, income or data;</li>
                <li>the acts, listings, decisions or content of employers, mentors, other users or third-party services;</li>
                <li>loss caused by events outside our reasonable control, such as network or power failures;</li>
                <li>loss caused by you not keeping your password or device secure.</li>
            </ul>
            <p>Nothing in these terms excludes or limits liability that cannot be excluded or limited by law, such as liability for death or personal injury caused by negligence, or for fraud.</p>
        </section>

        <section id="law">
            <h2>13. Governing law</h2>
            <p>These terms are governed by the laws of {{ $jurisdiction }}. Before starting any formal proceedings, please contact us so that we can try to resolve the matter informally. Any dispute that cannot be resolved this way will be handled by the courts of {{ $jurisdiction }}, unless the law gives you the right to bring a claim elsewhere.</p>
        </section>

        <section id="changes">
            <h2>14. Changes to these terms</h2>
            <p>We may update these terms from time to time. The "Last updated" date at the top shows when they last changed. If we make significant changes we will tell you through the platform, the app or by email. If you keep using the platform after the changes take effect, you accept the updated terms; if you do not agree, stop using the platform and ask us to delete your account.</p>
        </section>

        <section id="contact">
            <h2>15. Contact us</h2>
            <ul>
                <li>Email: <a href="mailto:{{ $email }}">{{ $email }}</a></li>
                <li>Organisation: {{ $org }}</li>
                <li>Address: {{ $address }}</li>
            </ul>
            <p>Please also read our <a href="{{ route('legal.privacy') }}">Privacy Policy</a>.</p>
        </section>

    </div>
</article>
@endsection
