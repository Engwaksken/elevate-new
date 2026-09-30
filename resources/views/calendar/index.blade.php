@extends(auth()->check() && method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff() ? 'layouts.admin' : 'layouts.app')

@section('title', 'Calendar | ElevateHer360')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Schedule</span>
        <h1>Calendar</h1>
        <p>View programme, course, mentorship and event activities in one place.</p>
    </div>
</div>

<div class="admin-panel">
    <div class="eh-tabs" data-eh-tabs>
        <div class="eh-tab-nav">
            <button class="eh-tab-button active" data-eh-tab="upcoming">
                <i class="fas fa-calendar-days"></i> Upcoming
            </button>
            <button class="eh-tab-button" data-eh-tab="past">
                <i class="fas fa-clock-rotate-left"></i> Past
            </button>
            <button class="eh-tab-button" data-eh-tab="all">
                <i class="fas fa-list"></i> All Events
            </button>
        </div>

        <div class="eh-tab-content">
            @foreach(['upcoming' => 'Upcoming Events', 'past' => 'Past Events', 'all' => 'All Events'] as $key => $title)
                @php
                    $filtered = $events->filter(function ($event) use ($key) {
                        if ($key === 'all') {
                            return true;
                        }

                        return $key === 'upcoming'
                            ? $event->starts_at->gte(now())
                            : $event->starts_at->lt(now());
                    });
                @endphp

                <section class="eh-tab-pane {{ $key === 'upcoming' ? 'active' : '' }}" data-eh-pane="{{ $key }}">
                    <div class="eh-tab-section">
                        <div class="eh-data-list">
                            @forelse($filtered as $event)
                                <div class="eh-data-row">
                                    <div class="eh-data-row-main">
                                        <span class="eh-data-row-icon">
                                            <i class="fas fa-calendar-check"></i>
                                        </span>
                                        <div class="eh-data-row-copy">
                                            <strong>{{ $event->title }}</strong>
                                            <span>
                                                {{ ucfirst(str_replace('_', ' ', $event->event_type)) }}
                                                · {{ $event->starts_at->format('d M Y H:i') }}
                                                @if($event->ends_at)
                                                    – {{ $event->ends_at->format('d M Y H:i') }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <span class="eh-status">{{ $event->status ?? 'Scheduled' }}</span>
                                </div>
                            @empty
                                <div class="eh-empty">
                                    <i class="fas fa-calendar"></i>
                                    <h3>No {{ strtolower($title) }}</h3>
                                    <p>Events will appear here when available.</p>
                                </div>
                            @endforelse
                        </div>

                        @if($key === 'all')
                            {{ $events->links() }}
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
@endsection
