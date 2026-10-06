<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\AssignSupportTicketRequest;
use App\Http\Requests\Support\IndexSupportTicketQueueRequest;
use App\Http\Requests\Support\UpdateSupportTicketStatusRequest;
use App\Models\ItSupportTicket;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class SupportTicketQueueController extends Controller
{
    use ExportsTables;
    public function index(IndexSupportTicketQueueRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        Gate::authorize('viewAny', ItSupportTicket::class);

        $query = ItSupportTicket::query()
            ->with(['requester', 'assignee'])
            ->when(! $request->user()->can('assign', new ItSupportTicket()), fn ($query) => $query->where('requester_id', $request->user()->id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->validated('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->validated('priority')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->validated('category')))
            ->when($request->filled('assignee_id'), fn ($query) => $request->validated('assignee_id') === 'none'
                ? $query->whereNull('assignee_id')
                : $query->where('assignee_id', (int) $request->validated('assignee_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->validated('search').'%';
                $query->where(fn ($q) => $q->where('subject', 'like', $term)->orWhere('description', 'like', $term)
                    ->orWhereHas('requester', fn ($r) => $r->where('name', 'like', $term)));
            })
            ->orderByRaw("case status when 'open' then 0 when 'in_progress' then 1 when 'awaiting_requester' then 2 else 3 end")
            ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 when 'medium' then 2 else 3 end")
            ->latest();

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'Support Tickets', $query, [
                'Ticket #' => 'id',
                'Subject' => 'subject',
                'Category' => fn ($r) => str_replace('_', ' ', (string) $r->category),
                'Priority' => fn ($r) => str_replace('_', ' ', (string) $r->priority),
                'Status' => fn ($r) => str_replace('_', ' ', (string) $r->status),
                'Requester' => 'requester.name',
                'Assignee' => 'assignee.name',
                'Opened' => 'created_at',
                'Status Updated' => 'status_updated_at',
            ]);
        }

        $tickets = $query
            ->paginate(24)
            ->withQueryString();

        $all = ItSupportTicket::query()->when(! $request->user()->can('assign', new ItSupportTicket()), fn ($q) => $q->where('requester_id', $request->user()->id));
        $stats = [
            'open' => (clone $all)->where('status', 'open')->count(),
            'in_progress' => (clone $all)->where('status', 'in_progress')->count(),
            'awaiting_requester' => (clone $all)->where('status', 'awaiting_requester')->count(),
            'unassigned' => (clone $all)->whereNull('assignee_id')->where('status', '!=', 'resolved')->count(),
        ];

        return view('support.tickets.index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'assignees' => $this->itTeam(),
        ]);
    }

    public function show(ItSupportTicket $ticket): View
    {
        Gate::authorize('view', $ticket);
        $ticket->load(['requester', 'assignee']);
        $assignees = $this->itTeam();

        return view('support.tickets.show', compact('ticket', 'assignees'));
    }

    /** Active IT Leads and Assistants: the people tickets can be assigned to. */
    private function itTeam()
    {
        return User::query()->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['it-lead', 'it-assistant'])->orWhereIn('name', ['IT Lead', 'IT Assistant']))
            ->orderBy('name')->get(['id', 'name']);
    }

    public function status(UpdateSupportTicketStatusRequest $request, ItSupportTicket $ticket, UserNotificationService $notifications, AuditService $audit): RedirectResponse
    {
        Gate::authorize('updateStatus', $ticket);
        $next = $request->validated('status');
        $allowed = match ($ticket->status) {
            'open' => ['in_progress'],
            'in_progress' => ['awaiting_requester', 'resolved'],
            'awaiting_requester' => ['in_progress', 'resolved'],
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

        return back()->with('success', 'Ticket status updated.');
    }

    public function assignee(AssignSupportTicketRequest $request, ItSupportTicket $ticket, UserNotificationService $notifications, AuditService $audit): RedirectResponse
    {
        Gate::authorize('assign', $ticket);
        $assigneeId = $request->validated('assignee_id');
        $previousAssigneeId = $ticket->assignee_id;
        if ($assigneeId !== null) {
            $assignee = User::findOrFail($assigneeId);
            Gate::authorize('eligibleAssignee', [ItSupportTicket::class, $assignee]);
            abort_unless($assignee->isActive() && $assignee->hasRole(['it-lead', 'it-assistant', 'IT Lead', 'IT Assistant']), 422, 'Assignee must be an active IT Lead or IT Assistant.');
        }

        if ($assigneeId === null) {
            if ($previousAssigneeId !== null) {
                $ticket->update(['assignee_id' => null]);
                $audit->log('it_support_ticket', 'assigned', $ticket, ['assignee_id' => $previousAssigneeId], ['assignee_id' => null], $request);
                $notifications->sendToMany(array_filter([$ticket->requester_id, $previousAssigneeId]),
                    'it_support_ticket', 'IT support ticket assignment updated',
                    'The assignee for your IT support request was removed.', null,
                    ['ticket_id' => $ticket->id, 'assignee_id' => null], false);
            }
        } elseif ($previousAssigneeId !== (int) $assigneeId) {
            $ticket->update(['assignee_id' => $assignee->id]);
            $audit->log('it_support_ticket', 'assigned', $ticket, ['assignee_id' => $previousAssigneeId], ['assignee_id' => $assignee->id], $request);
            $notifications->sendToMany(array_filter([$ticket->requester_id, $previousAssigneeId, $assignee->id]),
                'it_support_ticket', 'IT support ticket assignment updated',
                'The assignee for your IT support request was updated.', null,
                ['ticket_id' => $ticket->id, 'assignee_id' => $assignee->id], false);
        }

        return back()->with('success', 'Ticket assignment updated.');
    }
}
