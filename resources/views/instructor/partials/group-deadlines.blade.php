{{--
    Group (cohort) due dates for an assessment. Blank = the group uses the
    course-wide due date. Inside the create form ($assessment null) these are
    plain inputs; on a card they are their own form so a branch instructor can
    set their group's dates without editing the shared assessment.
--}}
@php
    $scopeCohorts = $scopeCohorts ?? collect();
    $assessment = $assessment ?? null;
    $current = $assessment ? $assessment->cohortDeadlines->keyBy('cohort_id') : collect();
@endphp
@if($scopeCohorts->isNotEmpty())
    @if($assessment)
        <details class="ag-more ag-wide">
            <summary>Group due dates ({{ $current->count() }})</summary>
            <form method="POST" action="{{ route('instructor.courses.assessments.group-deadlines', [$course, $assessment]) }}" class="icm-form-grid" style="margin-top:12px">
                @csrf @method('PUT')
                <p class="icm-muted full" style="margin:0">Set a different due date for a group. Leave it blank to use the course date{{ $assessment->due_at ? ' ('.$assessment->due_at->format('d M Y H:i').')' : '' }}. The group is notified when its date changes.</p>
                @foreach($scopeCohorts as $cohort)
                    <div>
                        <label for="cohortDue{{ $assessment->id }}_{{ $cohort->id }}">{{ $cohort->name }}</label>
                        <input type="datetime-local" id="cohortDue{{ $assessment->id }}_{{ $cohort->id }}" name="cohort_due[{{ $cohort->id }}]" value="{{ $current->get($cohort->id)?->due_at?->format('Y-m-d\TH:i') }}">
                    </div>
                @endforeach
                <div class="full"><button class="btn btn-primary btn-sm"><i class="fas fa-calendar-check"></i> Save group due dates</button></div>
            </form>
        </details>
    @else
        <details class="full">
            <summary><strong>Different due dates for some groups (optional)</strong></summary>
            <div class="icm-form-grid" style="margin-top:10px">
                <p class="icm-muted full" style="margin:0">Leave a group blank to use the due date above.</p>
                @foreach($scopeCohorts as $cohort)
                    <div>
                        <label for="newCohortDue{{ $cohort->id }}">{{ $cohort->name }}</label>
                        <input type="datetime-local" id="newCohortDue{{ $cohort->id }}" name="cohort_due[{{ $cohort->id }}]">
                    </div>
                @endforeach
            </div>
        </details>
    @endif
@endif
