<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\ParticipantGoal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Participant personal goals with progress tracking.
 * GET/POST /goals, PUT /goals/{goal}, PUT /goals/{goal}/progress, DELETE /goals/{goal}
 */
class GoalController extends Controller
{
    public function index(Request $request)
    {
        $goals = $request->user()->goals()->latest()->get();

        return response()->json([
            'goals' => $goals->map(fn (ParticipantGoal $goal) => $this->payload($goal))->values(),
            'summary' => [
                'total' => $goals->count(),
                'in_progress' => $goals->where('status', 'in_progress')->count(),
                'completed' => $goals->where('status', 'completed')->count(),
                'cancelled' => $goals->where('status', 'cancelled')->count(),
                'average_progress' => $goals->isEmpty() ? 0.0 : round((float) $goals->avg('progress_percent'), 2),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $goal = $request->user()->goals()->create($data + [
            'source' => 'self',
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Goal created.', 'goal' => $this->payload($goal)], 201);
    }

    public function update(Request $request, ParticipantGoal $goal)
    {
        $this->authorizeGoal($request, $goal);

        $goal->update($this->validated($request, true));

        return response()->json(['message' => 'Goal updated.', 'goal' => $this->payload($goal->fresh())]);
    }

    public function progress(Request $request, ParticipantGoal $goal)
    {
        $this->authorizeGoal($request, $goal);

        $data = $request->validate([
            'current_value' => ['sometimes', 'nullable', 'numeric'],
            'progress_percent' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'status' => ['sometimes', 'nullable', Rule::in(['not_started', 'in_progress', 'completed', 'cancelled'])],
        ]);

        $goal->fill(array_filter($data, fn ($value) => $value !== null));
        $goal->save();

        return response()->json(['message' => 'Progress saved.', 'goal' => $this->payload($goal->fresh())]);
    }

    public function destroy(Request $request, ParticipantGoal $goal)
    {
        $this->authorizeGoal($request, $goal);
        $goal->delete();

        return response()->json(['message' => 'Goal removed.']);
    }

    private function authorizeGoal(Request $request, ParticipantGoal $goal): void
    {
        abort_unless((int) $goal->user_id === (int) $request->user()->id, 403, 'This goal does not belong to you.');
    }

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'title' => [$updating ? 'sometimes' : 'required', 'string', 'max:190'],
            'description' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'category' => ['sometimes', 'nullable', Rule::in(['career', 'learning', 'personal', 'mentorship', 'other'])],
            'unit' => ['sometimes', 'nullable', 'string', 'max:40'],
            'baseline_value' => ['sometimes', 'nullable', 'numeric'],
            'target_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'current_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'target_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['sometimes', 'nullable', Rule::in(['low', 'medium', 'high'])],
            'status' => ['sometimes', 'nullable', Rule::in(['not_started', 'in_progress', 'completed', 'cancelled'])],
        ]);
    }

    private function payload(ParticipantGoal $goal): array
    {
        return [
            'id' => $goal->id,
            'title' => $goal->title,
            'description' => $goal->description,
            'category' => $goal->category,
            'unit' => $goal->unit,
            'baseline_value' => $goal->baseline_value !== null ? (float) $goal->baseline_value : null,
            'target_value' => $goal->target_value !== null ? (float) $goal->target_value : null,
            'current_value' => $goal->current_value !== null ? (float) $goal->current_value : null,
            'progress_percent' => (float) $goal->progress_percent,
            'start_date' => $goal->start_date?->format('Y-m-d'),
            'target_date' => $goal->target_date?->format('Y-m-d'),
            'priority' => $goal->priority,
            'status' => $goal->status,
            'source' => $goal->source,
            'mentor_comment' => $goal->mentor_comment,
            'mentor_reviewed_at' => $goal->mentor_reviewed_at?->toIso8601String(),
            'completed_at' => $goal->completed_at?->toIso8601String(),
            'created_at' => $goal->created_at?->toIso8601String(),
            'updated_at' => $goal->updated_at?->toIso8601String(),
        ];
    }
}
