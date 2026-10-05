<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreBrowserSupportTicketRequest;
use App\Models\ItSupportTicket;
use App\Services\AuditService;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\Gate;

class SupportTicketController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', ItSupportTicket::class);

        $tickets = ItSupportTicket::query()->where('requester_id', auth()->id())->latest()->paginate(15);

        return view('participant.support-tickets.index', compact('tickets'));
    }

    public function show(ItSupportTicket $ticket)
    {
        Gate::authorize('view', $ticket);

        return view('participant.support-tickets.show', compact('ticket'));
    }

    public function store(StoreBrowserSupportTicketRequest $request, UserNotificationService $notifications, AuditService $audit)
    {
        $ticket = ItSupportTicket::create([
            ...$request->validated(),
            'requester_id' => $request->user()->id,
            'status' => 'open',
        ]);

        $audit->log('it_support_ticket', 'created', $ticket, [], $ticket->only(['subject', 'category', 'priority', 'status']), $request);
        $supportUsers = \App\Models\User::query()->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['it-lead', 'it-assistant'])->orWhereIn('name', ['IT Lead', 'IT Assistant']))
            ->pluck('id');
        $notifications->sendToMany($supportUsers, 'it_support_ticket', 'New IT support ticket',
            'A new IT support request has been submitted.', null, ['ticket_id' => $ticket->id]);

        return back()->with('success', 'Your support request has been submitted.');
    }
}
