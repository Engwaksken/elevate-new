<?php

namespace App\Services;

use App\Models\ItSupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Single place that opens an IT support ticket. Used by the participant browser flow,
 * the mobile API and the staff Help & Support page so every request is audited and the
 * IT team is notified the same way.
 */
class ItSupportTicketService
{
    public const SUPPORT_ROLES = ['it-lead', 'it-assistant', 'IT Lead', 'IT Assistant'];

    public function __construct(
        private readonly UserNotificationService $notifications,
        private readonly AuditService $audit,
    ) {}

    /** @param array{subject:string,description:string,category?:string,priority?:string} $data */
    public function submit(User $requester, array $data, ?Request $request = null): ItSupportTicket
    {
        $ticket = ItSupportTicket::create([
            ...collect($data)->only(['subject', 'description', 'category', 'priority'])->filter(fn ($v) => $v !== null && $v !== '')->all(),
            'requester_id' => $requester->id,
            'status' => 'open',
        ]);

        $this->audit->log('it_support_ticket', 'created', $ticket, [], $ticket->only(['subject', 'category', 'priority', 'status']), $request);

        // Keep the notification free of ticket content: only the id travels.
        $this->notifications->sendToMany($this->supportTeamIds(), 'it_support_ticket', 'New IT support ticket',
            'A new IT support request has been submitted.', null, ['ticket_id' => $ticket->id]);

        return $ticket;
    }

    /** Active IT Leads / IT Assistants. */
    public function supportTeamIds(): Collection
    {
        return User::query()->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['it-lead', 'it-assistant'])->orWhereIn('name', ['IT Lead', 'IT Assistant']))
            ->pluck('id');
    }
}
