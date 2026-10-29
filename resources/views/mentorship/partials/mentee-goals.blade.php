{{-- Mentor view of mentees' personal goals: filters, paginated table, feedback in a modal. Expects $menteeGoals (paginator). --}}
<div class="eh-tab-section">
    <div class="eh-tab-section-header">
        <div><h2>Mentee goals</h2><p>Review your mentees' personal goals and leave encouraging, actionable feedback.</p></div>
        <x-export-buttons :params="['list' => 'mentee-goals']" />
    </div>
    <div class="admin-panel">
        @include('mentorship.partials.list-filters', ['prefix' => 'mg_', 'tab' => 'mentee-goals', 'placeholder' => 'Search goal, description or mentee...', 'periodLabel' => 'Due'])
        @if($menteeGoals->total() > 0)<p class="admin-cell-hint" style="margin:0 0 10px">Showing {{ $menteeGoals->firstItem() }}–{{ $menteeGoals->lastItem() }} of {{ number_format($menteeGoals->total()) }} goals</p>@endif
        <div class="admin-table-wrap"><table class="admin-table">
            <thead><tr><th>Mentee</th><th>Goal</th><th>Progress</th><th>Due</th><th>Status</th><th>Your last feedback</th><th class="table-actions">Actions</th></tr></thead>
            <tbody>
            @forelse($menteeGoals as $goal)
                <tr>
                    <td><strong>{{ $goal->user?->name ?? 'Mentee' }}</strong></td>
                    <td><strong>{{ $goal->title }}</strong><small class="admin-cell-hint">{{ ucfirst($goal->category) }}@if($goal->description) · {{ \Illuminate\Support\Str::limit($goal->description, 90) }}@endif</small></td>
                    <td>
                        {{ number_format((float) $goal->progress_percent, 0) }}%
                        @if($goal->target_value !== null)<small class="admin-cell-hint">{{ rtrim(rtrim(number_format((float) $goal->current_value, 2), '0'), '.') }} / {{ rtrim(rtrim(number_format((float) $goal->target_value, 2), '0'), '.') }} {{ $goal->unit }}</small>@endif
                    </td>
                    <td>{{ $goal->target_date?->format('d M Y') ?? '-' }}</td>
                    <td><span class="status-chip {{ $goal->status }}">{{ ucfirst(str_replace('_', ' ', $goal->status)) }}</span></td>
                    <td>@if($goal->mentor_comment){{ \Illuminate\Support\Str::limit($goal->mentor_comment, 80) }}<small class="admin-cell-hint">{{ optional($goal->mentor_reviewed_at)->format('d M Y') }} · {{ $goal->mentorReviewer?->name }}</small>@else - @endif</td>
                    <td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-modal-open="mentee-goal-feedback-{{ $goal->id }}"><i class="fas fa-comment-dots"></i> Give feedback</button></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="admin-empty">{{ request()->hasAny(['mg_q', 'mg_period']) ? 'No mentee goals match these filters.' : 'Your mentees have not set any goals yet.' }}</div></td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="admin-pagination">{{ $menteeGoals->links() }}</div>
    </div>
</div>

@foreach($menteeGoals as $goal)
<div class="eh-modal" id="mentee-goal-feedback-{{ $goal->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="mentee-goal-feedback-{{ $goal->id }}-title">
<div class="eh-modal-dialog">
    <form method="POST" action="{{ route('mentorship.participant-goals.review', $goal) }}">
        @csrf
        <div class="eh-modal-header">
            <div><h2 id="mentee-goal-feedback-{{ $goal->id }}-title">Feedback for {{ $goal->user?->name }}</h2><p>{{ $goal->title }} · {{ number_format((float) $goal->progress_percent, 0) }}% complete</p></div>
            <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="eh-modal-body">
            @if($goal->description)<p style="margin-top:0;color:#475467">{{ $goal->description }}</p>@endif
            <label for="mentor_comment_{{ $goal->id }}" style="font-weight:600;color:#344054;display:block;margin-bottom:6px">Your feedback</label>
            <textarea id="mentor_comment_{{ $goal->id }}" name="mentor_comment" maxlength="2000" rows="5" placeholder="Add guidance, next steps or encouragement...">{{ $goal->mentor_comment }}</textarea>
        </div>
        <div class="eh-modal-footer">
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
            <button class="btn btn-primary"><i class="fas fa-comment-dots"></i> Save feedback</button>
        </div>
    </form>
</div>
</div>
@endforeach
