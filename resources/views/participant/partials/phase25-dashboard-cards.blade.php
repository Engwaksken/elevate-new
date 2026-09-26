@if(isset($openCourseCalls) || isset($assignedSurveys))
<div class="eh-phase25-dashboard-grid">
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-eyebrow">Learning</span>
                <h2>Course Opportunities</h2>
            </div>
            @if(Route::has('participant.course-calls.index'))
                <a href="{{route('participant.course-calls.index')}}" class="btn btn-outline btn-sm">View All</a>
            @endif
        </div>

        @forelse($openCourseCalls ?? [] as $call)
            <a href="{{route('participant.course-calls.show',$call)}}" class="eh-dashboard-item">
                <div>
                    <strong>{{$call->title}}</strong>
                    <small>{{$call->course?->title}}</small>
                </div>
                <span>{{ucwords($courseApplicationStatuses[$call->id] ?? 'Apply')}}</span>
            </a>
        @empty
            <p>No open course opportunities.</p>
        @endforelse
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <span class="admin-eyebrow">M&E</span>
                <h2>My Surveys</h2>
            </div>
            @if(Route::has('participant.surveys.index'))
                <a href="{{route('participant.surveys.index')}}" class="btn btn-outline btn-sm">View All</a>
            @endif
        </div>

        @forelse($assignedSurveys ?? [] as $survey)
            <a href="{{route('participant.surveys.show',$survey)}}" class="eh-dashboard-item">
                <div>
                    <strong>{{$survey->title}}</strong>
                    <small>{{$survey->closes_at ? 'Closes '.$survey->closes_at->format('d M Y') : 'Open'}}</small>
                </div>
                <span>{{ucwords(str_replace('_',' ',$surveyStatuses[$survey->id] ?? 'not_started'))}}</span>
            </a>
        @empty
            <p>No surveys currently assigned.</p>
        @endforelse
    </section>
</div>
@endif
