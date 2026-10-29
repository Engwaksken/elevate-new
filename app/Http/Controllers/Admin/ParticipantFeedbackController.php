<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseFeedbackResponse;
use App\Models\EventFeedbackResponse;
use App\Support\ParticipantFeedbackAccess;
use App\Support\PeriodFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ParticipantFeedbackController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(ParticipantFeedbackAccess::canView($request->user()), 403);

        $courses = $this->filtered(CourseFeedbackResponse::query(), $request, 'course');
        $events = $this->filtered(EventFeedbackResponse::query(), $request, 'event');

        // Statistics follow the same filters as the tables.
        $courseCount = (clone $courses)->count();
        $eventCount = (clone $events)->count();
        $total = $courseCount + $eventCount;
        $sum = fn (string $column) => (float) (clone $courses)->sum($column) + (float) (clone $events)->sum($column);
        $satisfied = (clone $courses)->where('overall_rating', '>=', 4)->count() + (clone $events)->where('overall_rating', '>=', 4)->count();

        return view('admin.participant-feedback.index', [
            'courseResponses' => (clone $courses)->with(['course', 'user'])->latest('submitted_at')
                ->paginate(15, ['*'], 'courses_page')->withQueryString()->fragment('course-feedback'),
            'eventResponses' => (clone $events)->with(['event', 'user'])->latest('submitted_at')
                ->paginate(15, ['*'], 'events_page')->withQueryString()->fragment('event-feedback'),
            'stats' => [
                'total' => $total,
                'courses' => $courseCount,
                'events' => $eventCount,
                'average' => $total ? round($sum('overall_rating') / $total, 1) : null,
                'recommend' => $total ? round($sum('recommend_rating') / $total, 1) : null,
                'satisfied' => $total ? (int) round($satisfied / $total * 100) : null,
            ],
        ]);
    }

    private function filtered(Builder $query, Request $request, string $relation): Builder
    {
        if (($search = trim((string) $request->query('search'))) !== '') {
            $term = '%'.$search.'%';
            $query->where(fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                ->orWhereHas($relation, fn ($r) => $r->where('title', 'like', $term)));
        }

        if (in_array($request->query('rating'), ['low', 'mid', 'high'], true)) {
            match ($request->query('rating')) {
                'low' => $query->where('overall_rating', '<=', 2),
                'mid' => $query->where('overall_rating', 3),
                'high' => $query->where('overall_rating', '>=', 4),
            };
        }

        return PeriodFilter::apply($query, 'submitted_at', $request);
    }
}
