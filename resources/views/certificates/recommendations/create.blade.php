@extends('layouts.admin')
@section('title','Recommend Certificates | ElevateHer360')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Certificates</span>
    <h1>{{ $canApprove ? 'Recommend or Issue Certificates' : 'Recommend Participants for Certificates' }}</h1>
    <p>Choose a course or event, select participants and {{ $canApprove ? 'issue certificates now or send them for review' : 'send them to administrators for approval' }}.</p>
</div>
<div class="admin-page-actions">
    <a href="{{ route('certificates.recommendations.index') }}" class="btn btn-outline"><i class="fas fa-list-check"></i> Track recommendations</a>
</div>
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar cert-picker">
    <label class="cert-picker-field">
        <span>Course</span>
        <select name="course_id" onchange="this.form.event_id.value='';this.form.submit()">
            <option value="">Select a course</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected($target instanceof \App\Models\Course && $target->id === $course->id)>{{ $course->title }}</option>
            @endforeach
        </select>
    </label>
    <span class="cert-picker-or">or</span>
    <label class="cert-picker-field">
        <span>Event</span>
        <select name="event_id" onchange="this.form.course_id.value='';this.form.submit()">
            <option value="">Select an event</option>
            @foreach($events as $event)
                <option value="{{ $event->id }}" @selected($target instanceof \App\Models\Event && $target->id === $event->id)>{{ $event->title }}{{ $event->starts_at ? ' · '.$event->starts_at->format('d M Y') : '' }}</option>
            @endforeach
        </select>
    </label>
    <noscript><button class="btn btn-primary btn-sm">Load participants</button></noscript>
</form>

@if(! $target)
    <div class="admin-empty"><i class="fas fa-award"></i><strong>Choose a course or event</strong><span>{{ $courses->isEmpty() && $events->isEmpty() ? 'You have no courses or events assigned yet.' : 'Its participants will be listed here.' }}</span></div>
@elseif($participants->isEmpty())
    <div class="admin-empty"><i class="fas fa-users-slash"></i><strong>No participants</strong><span>Nobody is enrolled in or registered for {{ $target->title }} yet.</span></div>
@else
@php $isEvent = $target instanceof \App\Models\Event; @endphp
<form method="POST" action="{{ route('certificates.recommendations.store') }}">
    @csrf
    <input type="hidden" name="{{ $isEvent ? 'event_id' : 'course_id' }}" value="{{ $target->id }}">

    @if($errors->any())
        <div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>
    @endif

    <div class="admin-table-wrap">
    <table class="admin-table">
    <thead>
    <tr>
        <th style="width:34px"><input type="checkbox" data-select-all aria-label="Select all eligible participants"></th>
        <th>Participant</th>
        <th>{{ $isEvent ? 'Registration & attendance' : 'Enrolment & progress' }}</th>
        <th>Certificate status</th>
    </tr>
    </thead>
    <tbody>
    @foreach($participants as $row)
    @php
        $recommendation = $row['recommendation'];
        $locked = $row['certificate'] || $recommendation?->isPending();
    @endphp
    <tr class="{{ $locked ? 'cert-row-locked' : '' }}">
        <td>
            @unless($locked)
                <input type="checkbox" name="user_ids[]" value="{{ $row['user']->id }}" data-row-select @checked(in_array($row['user']->id, old('user_ids', [])))
                       aria-label="Select {{ $row['user']->name }}">
            @endunless
        </td>
        <td><strong>{{ $row['user']->name }}</strong><small class="admin-cell-hint">{{ $row['user']->email }}</small></td>
        <td>{{ $row['detail'] }}</td>
        <td>
            @if($row['certificate'])
                <span class="cert-status cert-status-approved">Certificate issued</span>
            @elseif($recommendation)
                @include('certificates.recommendations._status', ['status' => $recommendation->status])
                <small class="admin-cell-hint">by {{ $recommendation->recommender?->name ?? '—' }} · {{ $recommendation->created_at?->format('d M Y') }}</small>
            @else
                <span class="cert-status">Not recommended</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
    </table>
    </div>

    <div class="cert-submit">
        <div class="form-group">
            <label for="cert-reason">Reason / notes for the reviewer</label>
            <textarea id="cert-reason" name="reason" rows="3" maxlength="2000" placeholder="e.g. Completed all modules and the final project; attended every session.">{{ old('reason') }}</textarea>
        </div>
        <div class="cert-submit-actions">
            <button type="submit" class="btn btn-outline"><i class="fas fa-paper-plane"></i> Recommend selected</button>
            @if($canApprove)
                <button type="submit" name="issue_now" value="1" class="btn btn-primary"><i class="fas fa-award"></i> Issue certificates now</button>
            @endif
        </div>
    </div>
</form>
@endif
</div>
@endsection
