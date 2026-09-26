@extends('layouts.admin')

@section('title','Events Calendar | ElevateHer360')

@section('content')
@php
    $calendarStart = $start ?? now()->startOfMonth();
    $calendarEnd = $end ?? $calendarStart->copy()->endOfMonth();
    $calendarGridStart = $gridStart ?? $calendarStart->copy()->startOfWeek();
    $calendarGridEnd = $gridEnd ?? $calendarEnd->copy()->endOfWeek();
    $calendarEvents = collect($events ?? []);
@endphp

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Events</span>
        <h1>Events Calendar</h1>
        <p>Monthly view of scheduled ElevateHer360 events.</p>
    </div>

    <div class="admin-page-actions">
        @if(Route::has('admin.events.index'))
            <a href="{{ route('admin.events.index') }}" class="btn btn-outline">
                <i class="fas fa-list"></i>
                Events List
            </a>
        @endif
    </div>
</div>

<div class="admin-panel">
    <form method="GET" class="admin-toolbar">
        <div class="form-group">
            <label for="event-calendar-month">Month</label>
            <input
                id="event-calendar-month"
                type="month"
                name="month"
                value="{{ $calendarStart->format('Y-m') }}"
            >
        </div>

        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-calendar-days"></i>
            Go
        </button>
    </form>
</div>

<div class="event-calendar-grid">
    @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
        <div class="event-calendar-weekday">{{ $day }}</div>
    @endforeach

    @for($date = $calendarGridStart->copy(); $date->lte($calendarGridEnd); $date->addDay())
        @php
            $key = $date->format('Y-m-d');
            $dayEvents = collect($calendarEvents->get($key, collect()));
            $isOutsideMonth = $date->month !== $calendarStart->month;
        @endphp

        <div class="event-calendar-day {{ $isOutsideMonth ? 'outside' : '' }}">
            <div class="event-calendar-date">{{ $date->day }}</div>

            @forelse($dayEvents as $event)
                @php
                    $eventTime = data_get($event, 'starts_at');

                    if ($eventTime && ! $eventTime instanceof \Carbon\CarbonInterface) {
                        try {
                            $eventTime = \Carbon\Carbon::parse($eventTime);
                        } catch (\Throwable $e) {
                            $eventTime = null;
                        }
                    }
                @endphp

                @if(Route::has('admin.events.view'))
                    <a
                        href="{{ route('admin.events.view', $event) }}"
                        class="event-calendar-entry"
                    >
                        @if($eventTime)
                            <strong>{{ $eventTime->format('H:i') }}</strong>
                        @endif

                        <span>{{ data_get($event, 'title', 'Event') }}</span>
                    </a>
                @else
                    <div class="event-calendar-entry">
                        @if($eventTime)
                            <strong>{{ $eventTime->format('H:i') }}</strong>
                        @endif

                        <span>{{ data_get($event, 'title', 'Event') }}</span>
                    </div>
                @endif
            @empty
            @endforelse
        </div>
    @endfor
</div>
@endsection
