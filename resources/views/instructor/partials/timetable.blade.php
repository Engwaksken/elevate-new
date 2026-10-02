<section class="icm-panel">
    <div class="icm-panel-head"><div><h2>Course Timetable</h2><p class="icm-muted">Add dated sessions for all enrolled participants. Times use the selected timezone. Active sessions cannot overlap.</p></div></div>
    @if(session('success'))<div class="success-box" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="icm-notice" role="alert">{{ $errors->first() }}</div>@endif
    @include('instructor.partials.timetable-form', ['slot' => null])
</section>
<section class="icm-panel">
    <div class="icm-card-grid">
        @forelse($timeSlots as $timeSlot)
            @php($session = app(\App\Services\CourseTimetableService::class)->present($timeSlot))
            <article class="icm-card">
                <span class="status-chip {{ $session['status'] === 'cancelled' ? 'inactive' : 'active' }}">{{ $session['status'] === 'cancelled' ? 'Cancelled' : ($session['is_past'] ? 'Past session' : 'Scheduled') }}</span>
                <h3>{{ $session['title'] }}</h3>
                <p><strong>{{ $session['date_label'] }}</strong><br>{{ $session['time_label'] }} · {{ $session['timezone'] }}</p>
                @if($session['venue'])<p><i class="fas fa-location-dot"></i> {{ $session['venue'] }}</p>@endif
                @if($session['meeting_link'])<p><a href="{{ $session['meeting_link'] }}" target="_blank" rel="noopener noreferrer">Online meeting</a></p>@endif
                @if($session['notes'])<p style="white-space:pre-wrap">{{ $session['notes'] }}</p>@endif
                <details class="icm-details" @if((string) old('timetable_form') === (string) $timeSlot->id) open @endif>
                    <summary>Edit / cancel session</summary>
                    @include('instructor.partials.timetable-form', ['slot' => $timeSlot])
                </details>
                <form method="POST" action="{{ route('instructor.courses.timetable.destroy', [$course, $timeSlot]) }}" onsubmit="return confirm('Delete this session? Cancel it instead to keep it visible to participants.')" style="margin-top:12px">
                    @csrf @method('DELETE')<button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i> Delete</button>
                </form>
            </article>
        @empty
            <div class="icm-card">No time slots yet. Add the first session above.</div>
        @endforelse
    </div>
    <div style="margin-top:15px">{{ $timeSlots->appends(['tab' => 'timetable'])->links() }}</div>
</section>
