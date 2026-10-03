<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\ParticipantGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GoalController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $request->user()->goals()->create($data + [
            'source' => 'self',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Goal added. Keep going!');
    }

    public function update(Request $request, ParticipantGoal $goal): RedirectResponse
    {
        $this->authorizeGoal($request, $goal);

        $goal->update($this->validated($request, true));

        return back()->with('success', 'Goal updated.');
    }

    public function progress(Request $request, ParticipantGoal $goal): RedirectResponse
    {
        $this->authorizeGoal($request, $goal);

        $data = $request->validate([
            'current_value' => ['nullable', 'numeric'],
            'progress_percent' => ['nullable', 'numeric', 'between:0,100'],
            'status' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed', 'cancelled'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $goal->fill(array_filter([
            'current_value' => $data['current_value'] ?? null,
            'progress_percent' => $data['progress_percent'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn ($value) => $value !== null));

        $goal->save();

        return back()->with('success', 'Progress saved.');
    }

    public function destroy(Request $request, ParticipantGoal $goal): RedirectResponse
    {
        $this->authorizeGoal($request, $goal);
        $goal->delete();

        return back()->with('success', 'Goal removed.');
    }

    private function authorizeGoal(Request $request, ParticipantGoal $goal): void
    {
        abort_unless((int) $goal->user_id === (int) $request->user()->id, 403);
    }

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'title' => [$updating ? 'sometimes' : 'required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:4000'],
            'category' => ['nullable', Rule::in(['career', 'learning', 'personal', 'mentorship', 'other'])],
            'unit' => ['nullable', 'string', 'max:40'],
            'baseline_value' => ['nullable', 'numeric'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'status' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed', 'cancelled'])],
        ]);
    }
}
