<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseFeedbackResponse;
use App\Models\EventFeedbackResponse;
use App\Support\ParticipantFeedbackAccess;
use Illuminate\Http\Request;

class ParticipantFeedbackController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(ParticipantFeedbackAccess::canView($request->user()), 403);

        $courseResponses = CourseFeedbackResponse::with(['course', 'user'])
            ->latest('submitted_at')->paginate(15, ['*'], 'courses_page')->withQueryString();
        $eventResponses = EventFeedbackResponse::with(['event', 'user'])
            ->latest('submitted_at')->paginate(15, ['*'], 'events_page')->withQueryString();

        return view('admin.participant-feedback.index', compact('courseResponses', 'eventResponses'));
    }
}
