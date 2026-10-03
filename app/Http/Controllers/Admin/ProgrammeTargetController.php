<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Models\ProgrammeTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgrammeTargetController extends Controller
{
    public function store(Request $request, Programme $programme)
    {
        $target = $programme->targets()->create($this->validated($request) + [
            'created_by' => auth()->id(),
        ]);

        $programme->recalculateProgress();

        return back()->with('success', 'Programme target added.')->with('programme_target_saved', $target->id);
    }

    public function update(Request $request, ProgrammeTarget $target)
    {
        $target->update($this->validated($request));
        $target->programme?->recalculateProgress();

        return back()->with('success', 'Programme target updated.');
    }

    public function destroy(ProgrammeTarget $target)
    {
        $programme = $target->programme;
        $target->delete();
        $programme?->recalculateProgress();

        return back()->with('success', 'Programme target removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:4000'],
            'result_area' => ['nullable', 'string', 'max:190'],
            'unit' => ['nullable', 'string', 'max:40'],
            'baseline_value' => ['nullable', 'numeric'],
            'target_value' => ['required', 'numeric', 'min:0'],
            'achieved_value' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0.01', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', Rule::in(['not_started', 'in_progress', 'achieved', 'at_risk', 'cancelled'])],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
        ]);
    }
}
