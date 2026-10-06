<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignItSupportTicketRequest;
use App\Http\Requests\Api\V1\IndexItSupportTicketsRequest;
use App\Http\Requests\Api\V1\StoreItSupportTicketRequest;
use App\Http\Requests\Api\V1\UpdateItSupportTicketStatusRequest;
use App\Http\Resources\Api\V1\ItSupportTicketResource;
use App\Http\Resources\Api\V1\ItSupportTicketVolumeReportResource;
use App\Models\ItSupportTicket;
use App\Models\User;
use App\Services\UserNotificationService;
use App\Services\AuditService;
use App\Services\ItSupportTicketService;
use Illuminate\Support\Facades\Gate;

class ItSupportTicketController extends Controller
{
    public function index(IndexItSupportTicketsRequest $request)
    {
        Gate::authorize('viewAny', ItSupportTicket::class);

        $query = ItSupportTicket::query()->latest();
        if ($request->user()->can('assign', new ItSupportTicket())) {
            foreach (['status', 'priority', 'category', 'assignee_id'] as $filter) {
                if ($request->filled($filter)) {
                    $query->where($filter, $request->validated($filter));
                }
            }
        } else {
            $query->where('requester_id', $request->user()->id);
        }

        return ItSupportTicketResource::collection($query->paginate(15)->withQueryString());
    }

    public function store(StoreItSupportTicketRequest $request, ItSupportTicketService $tickets)
    {
        $ticket = $tickets->submit($request->user(), $request->validated(), $request);

        return (new ItSupportTicketResource($ticket))->response()->setStatusCode(201);
    }

    public function show(ItSupportTicket $ticket)
    {
        $user = request()->user();

        if (! $user->hasRole(['it-lead', 'it-assistant', 'IT Lead', 'IT Assistant'])
            && $ticket->requester_id !== $user->id) {
            abort(404);
        }

        Gate::authorize('view', $ticket);

        return new ItSupportTicketResource($ticket);
    }

    public function status(UpdateItSupportTicketStatusRequest $request, ItSupportTicket $ticket, UserNotificationService $notifications, AuditService $audit)
    {
        Gate::authorize('updateStatus', $ticket);
        $next = $request->validated('status');
        $allowed = match ($ticket->status) {
            'open' => ['in_progress'],
            'in_progress' => ['awaiting_requester', 'resolved'],
            'awaiting_requester' => ['in_progress', 'resolved'],
            'resolved' => [],
            default => [],
        };
        abort_unless(in_array($next, $allowed, true), 422, 'Invalid ticket status transition.');

        $previous = $ticket->status;
        $ticket->update(['status' => $next, 'status_updated_at' => now()]);
        $audit->log('it_support_ticket', 'status_updated', $ticket, ['status' => $previous], ['status' => $next], $request);
        if ($previous !== $next) {
            $notifications->sendToMany(array_filter([$ticket->requester_id, $ticket->assignee_id]),
                'it_support_ticket', 'IT support ticket status updated',
                'Your IT support request status is now '.str_replace('_', ' ', $next).'.', null,
                ['ticket_id' => $ticket->id, 'status' => $next], false);
        }

        return new ItSupportTicketResource($ticket->refresh());
    }

    public function assignee(AssignItSupportTicketRequest $request, ItSupportTicket $ticket, UserNotificationService $notifications, AuditService $audit)
    {
        Gate::authorize('assign', $ticket);
        $assigneeId = $request->validated('assignee_id');
        $previousAssigneeId = $ticket->assignee_id;
        if ($assigneeId === null) {
            if ($previousAssigneeId !== null) {
                $ticket->update(['assignee_id' => null]);
                $audit->log('it_support_ticket', 'assigned', $ticket, ['assignee_id' => $previousAssigneeId], ['assignee_id' => null], $request);
                $notifications->sendToMany(array_filter([$ticket->requester_id, $previousAssigneeId]),
                    'it_support_ticket', 'IT support ticket assignment updated',
                    'The assignee for your IT support request was removed.', null,
                    ['ticket_id' => $ticket->id, 'assignee_id' => null], false);
            }
            return new ItSupportTicketResource($ticket->refresh());
        }

        $assignee = User::findOrFail($assigneeId);
        Gate::authorize('eligibleAssignee', [ItSupportTicket::class, $assignee]);
        abort_unless($assignee->isActive() && $assignee->hasRole(['it-lead', 'it-assistant', 'IT Lead', 'IT Assistant']), 422, 'Assignee must be an active IT Lead or IT Assistant.');

        if ($previousAssigneeId !== $assignee->id) {
            $ticket->update(['assignee_id' => $assignee->id]);
            $audit->log('it_support_ticket', 'assigned', $ticket, ['assignee_id' => $previousAssigneeId], ['assignee_id' => $assignee->id], $request);
            $notifications->sendToMany(array_filter([$ticket->requester_id, $previousAssigneeId, $assignee->id]),
                'it_support_ticket', 'IT support ticket assignment updated',
                'The assignee for your IT support request was updated.', null,
                ['ticket_id' => $ticket->id, 'assignee_id' => $assignee->id], false);
        }

        return new ItSupportTicketResource($ticket->refresh());
    }

    public function volumeReport()
    {
        Gate::authorize('viewVolumeReport', ItSupportTicket::class);

        return new ItSupportTicketVolumeReportResource([
            'status' => ItSupportTicket::query()->selectRaw('status, COUNT(*) as count')->groupBy('status')->orderBy('status')->get(),
            'category' => ItSupportTicket::query()->selectRaw('category, COUNT(*) as count')->groupBy('category')->orderBy('category')->get(),
            'priority' => ItSupportTicket::query()->selectRaw('priority, COUNT(*) as count')->groupBy('priority')->orderBy('priority')->get(),
        ]);
    }
}
