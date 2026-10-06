<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Concerns\ExportsTables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreBrowserSupportTicketRequest;
use App\Models\ItSupportTicket;
use App\Services\AuditService;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\Gate;

class SupportTicketController extends Controller
{
    use ExportsTables;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', ItSupportTicket::class);

        $query = ItSupportTicket::query()->where('requester_id', auth()->id())->latest();

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'My Support Requests', $query, [
                'Reference' => fn ($t) => '#'.$t->id,
                'Subject' => 'subject',
                'Category' => fn ($t) => ucwords(str_replace('_', ' ', (string) $t->category)),
                'Priority' => fn ($t) => ucfirst((string) $t->priority),
                'Status' => fn ($t) => ucwords(str_replace('_', ' ', (string) $t->status)),
                'Submitted' => 'created_at',
                'Status Updated' => 'status_updated_at',
            ]);
        }

        $tickets = $query->paginate(15);

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
