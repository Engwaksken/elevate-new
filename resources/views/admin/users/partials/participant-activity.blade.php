@php
    $rangeLink = fn ($days) => route('admin.participants.index', array_filter(
        array_merge(request()->except(['page', 'activity_days']), ['activity_days' => $days]),
        fn ($v) => $v !== null && $v !== ''
    ));
    $recencyMax = max(1, collect($activity['recency'])->max('count'));
    $tiles = [
        ['active_participants', 'Active participants', 'fa-user-check'],
        ['lessons_started', 'Lessons started', 'fa-book-open'],
        ['lessons_completed', 'Lessons completed', 'fa-circle-check'],
        ['assessments_submitted', 'Assessments submitted', 'fa-file-pen'],
        ['attendance', 'Session attendances', 'fa-calendar-check'],
    ];
@endphp

<section class="admin-panel pa-viz" aria-labelledby="participantActivityTitle">
    <div class="pa-head">
        <div>
            <h2 id="participantActivityTitle">Participant activity{{ $scopeLabel ?? '' }}</h2>
            <p>{{ $activity['from']->format('d M Y') }} – {{ $activity['to']->format('d M Y') }}</p>
        </div>
        <nav class="pa-range" aria-label="Activity period">
            @foreach($activity['ranges'] as $days)
                <a href="{{ $rangeLink($days) }}" @class(['is-active' => $activity['days'] === $days])
                   @if($activity['days'] === $days) aria-current="true" @endif>{{ $days }} days</a>
            @endforeach
        </nav>
    </div>

    <div class="pa-tiles">
        @foreach($tiles as [$key, $label, $icon])
            <div class="pa-tile">
                <small><i class="fas {{ $icon }}" aria-hidden="true"></i> {{ $label }}</small>
                <strong>{{ number_format($activity['totals'][$key] ?? 0) }}</strong>
            </div>
        @endforeach
    </div>

    <div class="pa-grid">
        <figure class="pa-card">
            <figcaption>
                <strong>Daily activity</strong>
                <span>Participants active each day, and new registrations</span>
            </figcaption>
            <ul class="pa-legend">
                <li><span class="pa-key pa-key-1" aria-hidden="true"></span> Active participants</li>
                <li><span class="pa-key pa-key-2" aria-hidden="true"></span> New registrations</li>
            </ul>
            <div class="pa-line" data-participant-activity-chart
                 data-series='@json($activity['series'])'
                 role="img" aria-label="Line chart of active participants and new registrations per day. The same figures are in the table below."></div>
            <details class="pa-table">
                <summary>Show as table</summary>
                <div class="pa-table-scroll">
                    <table class="admin-table" data-no-mobile-compact>
                        <thead><tr><th>Date</th><th>Active participants</th><th>New registrations</th></tr></thead>
                        <tbody>
                        @foreach(array_reverse($activity['series']) as $point)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($point['date'])->format('D d M Y') }}</td>
                                <td>{{ number_format($point['active']) }}</td>
                                <td>{{ number_format($point['registrations']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </figure>

        <figure class="pa-card">
            <figcaption>
                <strong>Last sign-in</strong>
                <span>How recently participants signed in</span>
            </figcaption>
            <ul class="pa-bars">
                @foreach($activity['recency'] as $bucket)
                    <li title="{{ $bucket['label'] }}: {{ number_format($bucket['count']) }}">
                        <span class="pa-bar-label">{{ $bucket['label'] }}</span>
                        <span class="pa-bar-track">
                            <span class="pa-bar" style="width: {{ $bucket['count'] ? max(1, round($bucket['count'] / $recencyMax * 82, 1)) : 0 }}%"></span>
                            <span class="pa-bar-value">{{ number_format($bucket['count']) }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </figure>
    </div>
</section>
