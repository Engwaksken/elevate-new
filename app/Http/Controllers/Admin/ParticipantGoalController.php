<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParticipantGoal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets administrators review every participant's personal goals and progress.
 */
class ParticipantGoalController extends Controller
{
    public function index(Request $request): View
    {
        $query = ParticipantGoal::query()
            ->with(['user:id,name,email,participant_code', 'mentorReviewer:id,name'])
            ->latest();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('participant_code', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($participant = $request->integer('user_id')) {
            $query->where('user_id', $participant);
        }

        $perPage = in_array((int) $request->get('per_page'), [10, 20, 25, 50, 100], true)
            ? (int) $request->get('per_page')
            : 20;

        return view('admin.participant-goals.index', [
            'goals' => $query->paginate($perPage)->withQueryString(),
            'participants' => User::where('user_type', 'participant')->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'total' => ParticipantGoal::count(),
                'in_progress' => ParticipantGoal::where('status', 'in_progress')->count(),
                'completed' => ParticipantGoal::where('status', 'completed')->count(),
                'average' => ParticipantGoal::count()
                    ? round((float) ParticipantGoal::avg('progress_percent'), 1)
                    : 0,
            ],
        ]);
    }
}
