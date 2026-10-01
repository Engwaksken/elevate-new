{{-- Full history panel for review modals. Expects $history and optional $duplicates. --}}
<div class="ph-details">
    <h4><i class="fas fa-clock-rotate-left"></i> Participation history</h4>
    @if(empty($history['returning']))
        <p class="ph-muted">No previous courses or certificates — this is their first course with us.</p>
    @else
        <p class="ph-muted">{{ $history['summary'] }}</p>
        <ul class="ph-course-list">
            @foreach($history['courses'] as $course)
                <li><strong>{{ $course['title'] }}</strong><span>{{ ucfirst($course['status']) }}{{ $course['year'] ? ' · '.$course['year'] : '' }}</span></li>
            @endforeach
        </ul>
    @endif

    @if(isset($duplicates) && $duplicates->isNotEmpty())
        <div class="ph-duplicate-box" role="note">
            <strong><i class="fas fa-triangle-exclamation"></i> Other accounts share this phone number</strong>
            <ul>
                @foreach($duplicates as $duplicate)
                    <li>{{ $duplicate->name }} · {{ $duplicate->participant_code ?? 'no ID' }} · {{ $duplicate->email }}</li>
                @endforeach
            </ul>
            <span class="ph-muted">This may be the same person registering again. Check before enrolling them so their history stays under one participant ID.</span>
        </div>
    @endif
</div>
