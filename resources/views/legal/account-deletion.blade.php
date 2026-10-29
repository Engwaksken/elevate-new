@extends('layouts.app')

@section('title', 'Delete your account | ElevateHer360')

@section('meta_description', 'How to delete your ElevateHer360 account and personal data, from the mobile app or this page.')

@php
    $days = \App\Models\AccountDeletionRequest::DAYS_TO_PROCESS;
    $email = config('legal.support_email') ?: 'support@elevateher360.org';
@endphp

@section('content')
<article class="legal-page">
    @include('legal.partials.header', [
        'heading' => 'Delete your ElevateHer360 account',
        'intro' => 'You can ask us to delete your ElevateHer360 account and the personal data linked to it at any time. This applies to the website and the ElevateHer360 mobile app.',
    ])

    <div class="card legal-body">
        <section>
            <h2>How to ask for deletion</h2>
            <ol>
                <li><strong>In the mobile app:</strong> open <em>Profile</em>, then <em>Settings</em>, and tap <em>Delete my account</em>. Confirm with your password.</li>
                <li><strong>On this page:</strong> sign in and use the form below.</li>
                <li><strong>By email:</strong> if you cannot sign in, email <a href="mailto:{{ $email }}?subject=Account%20deletion%20request">{{ $email }}</a> from the address registered to your account, with the subject "Account deletion request".</li>
            </ol>
            <p>Our team reviews each request and completes it within {{ $days }} days. When it is done you are signed out of the app and website and can no longer sign in.</p>
        </section>

        <section>
            <h2>What is deleted</h2>
            <ul>
                <li>Your name, email address, phone number and password.</li>
                <li>Your profile, including photo, gender, date of birth, location, disability information, education, employment and career interests.</li>
                <li>Resumes, uploaded CVs and cover letters, saved jobs, personal goals, AI career mentor conversations and action plans.</li>
                <li>Library bookmarks, reading history and download requests, notifications, and app sign-in and push notification tokens.</li>
            </ul>
            <h2>What is kept, without your identity</h2>
            <ul>
                <li>Programme records such as course enrolments, assessment results, certificates, attendance, event registrations, feedback and job applications are kept for programme reporting, but they are no longer linked to your name or contact details.</li>
                <li>Records we must keep by law, for no longer than necessary.</li>
            </ul>
            <p>Deletion is permanent. Download any certificates or documents you want to keep before you ask.</p>
        </section>

        <section>
            <h2>Request deletion</h2>
            @auth
                @if($pending)
                    <div class="legal-callout"><p><strong>Your request is being processed.</strong> You asked on {{ $pending->created_at->format('d M Y') }}. Your account will be deleted by {{ $pending->created_at->addDays($days)->format('d M Y') }}.</p></div>
                @else
                    <form method="POST" action="{{ route('account-deletion.store') }}" style="max-width:520px">
                        @csrf
                        <div class="form-group">
                            <label for="deletion-password">Your password *</label>
                            <input id="deletion-password" type="password" name="password" required autocomplete="current-password">
                            @error('password')<small class="form-error">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group">
                            <label for="deletion-reason">Why are you leaving? (optional)</label>
                            <textarea id="deletion-reason" name="reason" rows="3" maxlength="1000">{{ old('reason') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="modal-check"><input type="checkbox" name="confirm" value="1" required> <span>I understand my account and personal data will be permanently deleted.</span></label>
                            @error('confirm')<small class="form-error">{{ $message }}</small>@enderror
                        </div>
                        <button class="btn btn-danger"><i class="fas fa-user-xmark"></i> Ask to delete my account</button>
                    </form>
                @endif
            @else
                <p>Please sign in so we can confirm the request comes from you.</p>
                <a class="btn btn-primary" href="{{ route('login') }}"><i class="fas fa-right-to-bracket"></i> Sign in to continue</a>
            @endauth
        </section>
    </div>
</article>
@endsection
