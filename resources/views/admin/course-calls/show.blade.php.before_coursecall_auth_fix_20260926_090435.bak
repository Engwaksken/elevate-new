@extends('layouts.admin')
@section('title',$courseCall->title.' | ElevateHer360')

@section('content')
@php
    $applicationUrl = route('participant.course-calls.show', $courseCall);
@endphp

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Course Call</span>
        <h1>{{ $courseCall->title }}</h1>
        <p>
            {{ ucfirst($courseCall->status) }}
            @if($courseCall->closes_at)
                · Closes {{ $courseCall->closes_at->format('d M Y H:i') }}
            @endif
        </p>
    </div>

    <div class="admin-page-actions">
        <a href="{{ route('admin.course-calls.edit', $courseCall) }}" class="btn btn-primary">
            <i class="fas fa-pen"></i>
            Edit
        </a>

        <a href="{{ route('admin.course-calls.applications', $courseCall) }}" class="btn btn-outline">
            <i class="fas fa-file-lines"></i>
            Applications
        </a>

        <a href="{{ route('admin.course-calls.index') }}" class="btn btn-outline">
            Back
        </a>
    </div>
</div>

<div class="admin-stats-grid compact">
    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-graduation-cap"></i></span>
        <div>
            <small>Included Courses</small>
            <strong>{{ number_format($courseCall->courses->count()) }}</strong>
        </div>
    </div>

    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-file-lines"></i></span>
        <div>
            <small>Applications</small>
            <strong>{{ number_format($courseCall->applications_count) }}</strong>
        </div>
    </div>

    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-users"></i></span>
        <div>
            <small>Available Slots</small>
            <strong>{{ $courseCall->available_slots ?? '—' }}</strong>
        </div>
    </div>

    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-circle-info"></i></span>
        <div>
            <small>Status</small>
            <strong>{{ ucfirst($courseCall->status) }}</strong>
        </div>
    </div>
</div>

<div class="eh-detail-tabs" data-course-call-tabs>
    <button type="button" class="eh-detail-tab active" data-tab-target="courses">
        <i class="fas fa-graduation-cap"></i>
        Included Courses
    </button>

    <button type="button" class="eh-detail-tab" data-tab-target="description">
        <i class="fas fa-align-left"></i>
        Description
    </button>

    <button type="button" class="eh-detail-tab" data-tab-target="information">
        <i class="fas fa-circle-info"></i>
        Call Information
    </button>

    <button type="button" class="eh-detail-tab" data-tab-target="questions">
        <i class="fas fa-list-check"></i>
        Application Questions
    </button>

    <button type="button" class="eh-detail-tab" data-tab-target="share">
        <i class="fas fa-qrcode"></i>
        QR / Link
    </button>
</div>

<section class="admin-panel eh-detail-tab-panel" data-tab-panel="courses">
    <div class="admin-panel-head">
        <div>
            <h2>Included Courses</h2>
            <p>Participants can be approved into one of these courses.</p>
        </div>
    </div>

    <div class="eh-course-call-course-grid">
        @forelse($courseCall->courses as $course)
            <div class="eh-course-call-course-card">
                <i class="fas fa-graduation-cap"></i>

                <div>
                    <strong>{{ $course->title }}</strong>
                    <small>{{ ucfirst($course->status) }}</small>
                </div>
            </div>
        @empty
            <p>No courses are attached to this Course Call.</p>
        @endforelse
    </div>
</section>

<section class="admin-panel eh-detail-tab-panel" data-tab-panel="description" hidden>
    <h2>Description</h2>
    <p>{!! nl2br(e($courseCall->description ?: 'No description provided.')) !!}</p>

    <hr>

    <h2>Eligibility Criteria</h2>
    <p>{!! nl2br(e($courseCall->eligibility_criteria ?: 'No eligibility criteria provided.')) !!}</p>
</section>

<section class="admin-panel eh-detail-tab-panel" data-tab-panel="information" hidden>
    <h2>Call Information</h2>

    <div class="eh-details-grid">
        <div>
            <small>Programme</small>
            <strong>{{ $courseCall->programme?->name ?? '—' }}</strong>
        </div>

        <div>
            <small>Project</small>
            <strong>{{ $courseCall->project?->name ?? '—' }}</strong>
        </div>

        <div>
            <small>Cohort</small>
            <strong>{{ $courseCall->cohort?->name ?? '—' }}</strong>
        </div>

        <div>
            <small>Opens</small>
            <strong>{{ $courseCall->opens_at?->format('d M Y H:i') ?? '—' }}</strong>
        </div>

        <div>
            <small>Closes</small>
            <strong>{{ $courseCall->closes_at?->format('d M Y H:i') ?? '—' }}</strong>
        </div>

        <div>
            <small>Available Slots</small>
            <strong>{{ $courseCall->available_slots ?? '—' }}</strong>
        </div>

        <div>
            <small>Applications</small>
            <strong>{{ number_format($courseCall->applications_count) }}</strong>
        </div>

        <div>
            <small>Status</small>
            <strong>{{ ucfirst($courseCall->status) }}</strong>
        </div>
    </div>
</section>

<section class="admin-panel eh-detail-tab-panel" data-tab-panel="questions" hidden>
    <div class="admin-panel-head">
        <div>
            <h2>Application Questions</h2>
            <p>{{ number_format($courseCall->questions->count()) }} question(s)</p>
        </div>
    </div>

    @forelse($courseCall->questions as $question)
        <div class="survey-question-row">
            <div>
                <strong>{{ $question->question_text }}</strong>

                <small class="admin-cell-hint">
                    {{ ucwords(str_replace('_',' ',$question->question_type)) }}
                    {{ $question->is_required ? ' · Required' : '' }}
                </small>
            </div>
        </div>
    @empty
        <p>No custom application questions have been configured.</p>
    @endforelse
</section>

<section class="admin-panel eh-detail-tab-panel" data-tab-panel="share" hidden>
    <div class="eh-course-call-share-grid">
        <div>
            <span class="admin-eyebrow">Participant Application Link</span>
            <h2>Share Course Call</h2>
            <p>
                Participants who open this link will be required to sign in before
                accessing the Course Call application.
            </p>

            <div class="eh-copy-link-row">
                <input
                    id="courseCallApplicationLink"
                    type="text"
                    value="{{ $applicationUrl }}"
                    readonly
                >

                <button
                    type="button"
                    class="btn btn-primary"
                    data-copy-course-call-link
                >
                    <i class="fas fa-copy"></i>
                    Copy Link
                </button>
            </div>

            <small id="courseCallCopyStatus" class="admin-cell-hint"></small>
        </div>

        <div class="eh-course-call-qr">
            <img
                src="{{ route('admin.course-calls.qr', $courseCall) }}"
                alt="QR code for {{ $courseCall->title }}"
            >

            <a
                href="{{ route('admin.course-calls.qr', $courseCall) }}"
                target="_blank"
                class="btn btn-outline btn-sm"
            >
                <i class="fas fa-up-right-from-square"></i>
                Open QR
            </a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = [...document.querySelectorAll('[data-course-call-tabs] [data-tab-target]')];
    const panels = [...document.querySelectorAll('[data-tab-panel]')];

    const activate = (name) => {
        tabs.forEach((tab) => {
            tab.classList.toggle('active', tab.dataset.tabTarget === name);
        });

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.tabPanel !== name;
        });

        try {
            sessionStorage.setItem(
                'admin-course-call-view-tab-{{ $courseCall->id }}',
                name
            );
        } catch (error) {
            // Session storage is optional.
        }
    };

    let initial = 'courses';

    try {
        initial = sessionStorage.getItem(
            'admin-course-call-view-tab-{{ $courseCall->id }}'
        ) || 'courses';
    } catch (error) {
        initial = 'courses';
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => activate(tab.dataset.tabTarget));
    });

    activate(initial);

    document.querySelector('[data-copy-course-call-link]')?.addEventListener('click', async () => {
        const input = document.getElementById('courseCallApplicationLink');
        const status = document.getElementById('courseCallCopyStatus');

        try {
            await navigator.clipboard.writeText(input.value);
            status.textContent = 'Link copied.';
        } catch (error) {
            input.select();
            document.execCommand('copy');
            status.textContent = 'Link copied.';
        }

        setTimeout(() => {
            status.textContent = '';
        }, 3000);
    });
});
</script>
@endpush
