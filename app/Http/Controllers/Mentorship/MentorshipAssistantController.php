<?php

namespace App\Http\Controllers\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\CareerActionItem;
use App\Models\CareerMentorMessage;
use App\Services\MentorshipAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MentorshipAssistantController extends Controller
{
    public function message(Request $request, MentorshipAssistantService $assistant): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1500'],
            'history' => ['nullable', 'array', 'max:12'],
        ]);

        $reply = $assistant->reply($request->user(), $data['message'], $data['history'] ?? []);

        return response()->json($reply);
    }

    /** Recent conversation (oldest first) and whether the AI mentor is switched on. Used by the mobile app. */
    public function history(Request $request): JsonResponse
    {
        $messages = CareerMentorMessage::with('actions.goal')->where('user_id', $request->user()->id)
            ->latest('id')->take(30)->get()->reverse()->values();

        return response()->json([
            'enabled' => (bool) \App\Models\AiIntegration::activeSystem()?->encrypted_api_key,
            'messages' => $messages->map->toChatArray()->all(),
        ]);
    }

    /** Approved and completed steps ("My action plan"), planned first then by due date. */
    public function plan(Request $request): JsonResponse
    {
        $status = in_array($request->query('status'), [CareerActionItem::APPROVED, CareerActionItem::COMPLETED], true)
            ? [$request->query('status')] : [CareerActionItem::APPROVED, CareerActionItem::COMPLETED];

        $page = CareerActionItem::with('goal')->where('user_id', $request->user()->id)->whereIn('status', $status)
            ->orderByRaw("status = 'completed'")->orderBy('due_on')->paginate(20);

        return response()->json([
            'data' => collect($page->items())->map->toChatArray()->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    /** Answers the participant saved, newest first. */
    public function saved(Request $request): JsonResponse
    {
        $page = CareerMentorMessage::where('user_id', $request->user()->id)->where('role', 'assistant')
            ->whereNotNull('saved_at')->latest('saved_at')->paginate(20);

        return response()->json([
            'data' => collect($page->items())->map(fn ($m) => ['id' => $m->id, 'content' => $m->content, 'saved_at' => $m->saved_at?->toIso8601String()])->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    /** Pin or unpin an answer so it stays in "Saved answers". */
    public function toggleSave(Request $request, CareerMentorMessage $message): JsonResponse
    {
        abort_unless($message->user_id === $request->user()->id && $message->role === 'assistant', 404);

        $message->update(['saved_at' => $message->saved_at ? null : now()]);

        return response()->json(['id' => $message->id, 'saved' => $message->saved_at !== null]);
    }

    /** Clear the conversation but keep saved answers and any step already approved. */
    public function clearHistory(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $deleted = DB::transaction(function () use ($userId) {
            CareerActionItem::where('user_id', $userId)->where('status', CareerActionItem::SUGGESTED)
                ->whereHas('message', fn ($q) => $q->whereNull('saved_at'))->delete();

            return CareerMentorMessage::where('user_id', $userId)->whereNull('saved_at')
                ->whereDoesntHave('actions')->delete();
        });

        return response()->json(['deleted' => $deleted]);
    }

    /** The participant confirms a suggested step (optionally editing it); it is added to her calendar. */
    public function approveAction(Request $request, CareerActionItem $action): JsonResponse
    {
        $this->authorizeAction($request, $action);
        abort_if($action->status !== CareerActionItem::SUGGESTED, 422, 'This step has already been handled.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'due_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addYears(2)->toDateString()],
        ]);

        DB::transaction(function () use ($action, $data) {
            $event = CalendarEvent::create([
                'eventable_type' => $action->getMorphClass(),
                'eventable_id' => $action->id,
                'title' => 'Career step: '.$data['title'],
                'event_type' => CareerActionItem::CALENDAR_TYPE,
                'description' => $action->goal ? 'Goal: '.$action->goal->title : null,
                'starts_at' => $data['due_on'].' 09:00:00',
                'all_day' => true,
                'responsible_user_id' => $action->user_id,
                'status' => 'scheduled',
            ]);

            $action->update([
                'title' => $data['title'],
                'due_on' => $data['due_on'],
                'status' => CareerActionItem::APPROVED,
                'approved_at' => now(),
                'calendar_event_id' => $event->id,
            ]);
        });

        return response()->json($action->fresh('goal')->toChatArray());
    }

    public function dismissAction(Request $request, CareerActionItem $action): JsonResponse
    {
        $this->authorizeAction($request, $action);
        abort_if($action->status !== CareerActionItem::SUGGESTED, 422, 'This step has already been handled.');

        $action->update(['status' => CareerActionItem::DISMISSED]);

        return response()->json($action->toChatArray());
    }

    /** Mark an approved step done (or reopen it); the calendar entry follows. */
    public function toggleComplete(Request $request, CareerActionItem $action): JsonResponse
    {
        $this->authorizeAction($request, $action);
        abort_unless(in_array($action->status, [CareerActionItem::APPROVED, CareerActionItem::COMPLETED], true), 422, 'Approve this step first.');

        $done = $action->status === CareerActionItem::APPROVED;
        $action->update(['status' => $done ? CareerActionItem::COMPLETED : CareerActionItem::APPROVED, 'completed_at' => $done ? now() : null]);
        $action->calendarEvent?->update(['status' => $done ? 'completed' : 'scheduled']);

        return response()->json($action->toChatArray());
    }

    private function authorizeAction(Request $request, CareerActionItem $action): void
    {
        abort_unless($action->user_id === $request->user()->id, 404);
    }
}
