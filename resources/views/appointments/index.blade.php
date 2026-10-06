@extends('layouts.app')
@section('title', 'Appointments | ElevateHer360')
@section('content')
@php
    use App\Models\InstructorAppointment;
    $bookingFields = ['instructor_user_id', 'course_id', 'date', 'start_time', 'duration_minutes', 'mode', 'topic', 'details'];
    $reopenBooking = $errors->hasAny($bookingFields) || old('topic') !== null;
    $courseMap = $instructorOptions->mapWithKeys(fn ($row) => [
        $row['instructor']->id => $row['courses']->map(fn ($c) => ['id' => $c->id, 'title' => $c->title])->values(),
    ]);
    $selectedInstructor = (int) old('instructor_user_id', $instructorOptions->count() === 1 ? $instructorOptions->first()['instructor']->id : 0);
    $initialCourses = $selectedInstructor ? ($courseMap[$selectedInstructor] ?? collect()) : collect();
@endphp
@include('appointments._styles')
<style>
.appt-page input,.appt-page select,.appt-page textarea{font:inherit}
.appt-page .page-header{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:12px}
.appt-page .page-header-actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
</style>
<div class="appt-page">
    <div class="page-header">
        <div>
            <span class="eh-kicker">Growth</span>
            <h1>Instructor appointments</h1>
            <p>Book one-to-one time with an instructor of your courses. Your instructor approves the request or suggests another time.</p>
        </div>
        <div class="page-header-actions">
            <x-export-buttons />
            @if($instructorOptions->isNotEmpty())
                <button type="button" class="btn btn-primary" data-modal-open="appt-book"><i class="fas fa-calendar-plus" aria-hidden="true"></i> Book an appointment</button>
            @endif
        </div>
    </div>

    <div class="appt-stats" role="list" aria-label="Appointment summary">
        @foreach([['pending', 'Awaiting instructor', 'fa-hourglass-half'], ['proposals', 'New times to review', 'fa-calendar-plus'], ['upcoming', 'Confirmed upcoming', 'fa-calendar-check'], ['completed', 'Completed', 'fa-circle-check']] as [$key, $label, $icon])
            <div class="appt-stat" role="listitem"><i class="fas {{ $icon }}" aria-hidden="true"></i><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div></div>
        @endforeach
    </div>

    @if($instructorOptions->isEmpty())
        <div class="appt-empty" style="margin-bottom:20px"><i class="fas fa-chalkboard-user" aria-hidden="true"></i>
            You can book an appointment once you are enrolled in a course that has an assigned instructor.
            @if(Route::has('learning.index'))<div style="margin-top:10px"><a href="{{ route('learning.index') }}" class="btn btn-outline btn-sm">Browse courses</a></div>@endif
        </div>
    @endif

    <section class="appt-section" aria-labelledby="appt-upcoming-heading">
        <h2 id="appt-upcoming-heading"><i class="fas fa-calendar-day" aria-hidden="true"></i> Upcoming and awaiting response</h2>
        @if($upcoming->isEmpty())
            <div class="appt-empty"><i class="fas fa-calendar" aria-hidden="true"></i>No upcoming appointments.</div>
        @else
            <div class="appt-list">
                @foreach($upcoming as $appointment)
                    @include('appointments._participant-card', ['appointment' => $appointment])
                @endforeach
            </div>
        @endif
    </section>

    <section class="appt-section" aria-labelledby="appt-past-heading">
        <h2 id="appt-past-heading"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i> Past and closed</h2>
        @if($past->isEmpty())
            <div class="appt-empty"><i class="fas fa-box-archive" aria-hidden="true"></i>Nothing here yet.</div>
        @else
            <div class="appt-list">
                @foreach($past as $appointment)
                    @include('appointments._participant-card', ['appointment' => $appointment])
                @endforeach
            </div>
            <div style="margin-top:12px">{{ $past->links() }}</div>
        @endif
    </section>
</div>

@if($instructorOptions->isNotEmpty())
<div class="eh-modal" id="appt-book" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="appt-book-title" @if($reopenBooking) data-modal-autoopen @endif>
    <div class="eh-modal-dialog">
        <form method="POST" action="{{ route('appointments.store') }}" novalidate>
            @csrf
            <div class="eh-modal-header">
                <div><h2 id="appt-book-title">Book an appointment</h2><p>Your instructor will approve the time or propose another one.</p></div>
                <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button>
            </div>
            <div class="eh-modal-body">
                @if($errors->hasAny($bookingFields))
                    <div class="appt-note appt-note--reason" role="alert" style="margin-bottom:14px"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> {{ $errors->first() }}</div>
                @endif
                <ul class="appt-rules">
                    <li>Choose a time in the future; you can have up to {{ \App\Services\AppointmentService::MAX_OPEN_REQUESTS }} requests awaiting a reply.</li>
                    <li>Approved appointments can be cancelled up to {{ \App\Services\AppointmentService::CANCEL_CUTOFF_HOURS }} hours before they start.</li>
                </ul>
                <div class="modal-grid">
                    <div class="form-group">
                        <label for="appt-instructor">Instructor *</label>
                        <select id="appt-instructor" name="instructor_user_id" required data-appt-instructor @error('instructor_user_id') aria-invalid="true" aria-describedby="appt-instructor-error" @enderror>
                            <option value="">Select instructor</option>
                            @foreach($instructorOptions as $row)
                                <option value="{{ $row['instructor']->id }}" @selected($selectedInstructor === $row['instructor']->id)>{{ $row['instructor']->name }}</option>
                            @endforeach
                        </select>
                        @error('instructor_user_id')<span class="appt-hint" id="appt-instructor-error" style="color:#b42318">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="appt-course">Course *</label>
                        <select id="appt-course" name="course_id" required data-appt-course data-selected="{{ old('course_id') }}" @error('course_id') aria-invalid="true" @enderror>
                            @if($initialCourses->isEmpty())<option value="">Select an instructor first</option>@endif
                            @foreach($initialCourses as $course)
                                <option value="{{ $course['id'] }}" @selected((int) old('course_id') === $course['id'])>{{ $course['title'] }}</option>
                            @endforeach
                        </select>
                        <span class="appt-hint">Only courses this instructor teaches you are listed.</span>
                    </div>
                    <div class="form-group">
                        <label for="appt-date">Date *</label>
                        <input id="appt-date" type="date" name="date" required value="{{ old('date') }}" min="{{ now()->toDateString() }}" max="{{ now()->addDays(\App\Services\AppointmentService::MAX_DAYS_AHEAD)->toDateString() }}" @error('date') aria-invalid="true" aria-describedby="appt-date-error" @enderror>
                        @error('date')<span class="appt-hint" id="appt-date-error" style="color:#b42318">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="appt-time">Start time *</label>
                        <input id="appt-time" type="time" name="start_time" required step="300" value="{{ old('start_time') }}" @error('start_time') aria-invalid="true" @enderror>
                        <span class="appt-hint">Times are in {{ config('app.timezone') }}.</span>
                    </div>
                    <div class="form-group">
                        <label for="appt-duration">Duration *</label>
                        <select id="appt-duration" name="duration_minutes" required>
                            @foreach(InstructorAppointment::DURATIONS as $minutes)
                                <option value="{{ $minutes }}" @selected((int) old('duration_minutes', 30) === $minutes)>{{ $minutes }} minutes</option>
                            @endforeach
                        </select>
                    </div>
                    <fieldset class="form-group" style="border:0;margin:0;padding:0;min-width:0">
                        <legend style="margin-bottom:6px;font-size:.78rem;font-weight:700;color:#172033">Mode *</legend>
                        <div class="appt-modes">
                            @foreach(InstructorAppointment::MODES as $value => $label)
                                <label><input type="radio" name="mode" value="{{ $value }}" @checked(old('mode', 'online') === $value)> <i class="fas {{ $value === 'online' ? 'fa-video' : 'fa-location-dot' }}" aria-hidden="true"></i> {{ $label }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div class="form-group full">
                        <label for="appt-topic">Topic *</label>
                        <input id="appt-topic" name="topic" required maxlength="150" value="{{ old('topic') }}" placeholder="e.g. Help with Module 2 assignment" @error('topic') aria-invalid="true" @enderror>
                    </div>
                    <div class="form-group full">
                        <label for="appt-details">Details (optional)</label>
                        <textarea id="appt-details" name="details" maxlength="2000" rows="3" placeholder="Anything the instructor should know beforehand">{{ old('details') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="eh-modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button class="btn btn-primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> Send request</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const courses = @json($courseMap);
    const instructor = document.querySelector('[data-appt-instructor]');
    const course = document.querySelector('[data-appt-course]');
    if (!instructor || !course) return;
    const fill = () => {
        const list = courses[instructor.value] || [];
        const keep = course.value || course.dataset.selected;
        course.replaceChildren();
        if (!list.length) {
            course.append(new Option('Select an instructor first', ''));
            return;
        }
        list.forEach(c => course.append(new Option(c.title, c.id, false, String(c.id) === String(keep))));
    };
    instructor.addEventListener('change', () => { course.dataset.selected = ''; course.value = ''; fill(); });
    fill();
});
</script>
@endif
@endsection
