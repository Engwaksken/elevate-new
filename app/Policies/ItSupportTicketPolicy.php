<?php

namespace App\Policies;

use App\Http\Controllers\Api\V1\ItSupportTicketController as ApiItSupportTicketController;
use App\Http\Controllers\Participant\SupportTicketController as ParticipantSupportTicketController;
use App\Models\ItSupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class ItSupportTicketPolicy
{
    /** IT support sees the shared queue; requesters may list their own tickets on requester endpoints. */
    public function viewAny(User $user): bool
    {
        if ($this->canOversee($user)) {
            return true;
        }

        if (! $user->isActive()) {
            return false;
        }

        $action = Route::currentRouteAction();

        return in_array($action, [
            ApiItSupportTicketController::class.'@index',
            ParticipantSupportTicketController::class.'@index',
        ], true);
    }

    public function view(User $user, ItSupportTicket $ticket): bool
    {
        return $this->canOversee($user)
            || ($user->isActive() && $ticket->requester_id === $user->id);
    }

    /** Active users may submit a ticket; controller must set requester_id to the actor. */
    public function create(User $user): bool
    {
        return $user->isActive();
    }

    /** Ticket details and status/assignment may only be changed by the support team. */
    public function update(User $user, ItSupportTicket $ticket): bool
    {
        return $this->canOversee($user);
    }

    public function delete(User $user, ItSupportTicket $ticket): bool
    {
        return false;
    }

    public function updateStatus(User $user, ItSupportTicket $ticket): bool
    {
        return $this->canOversee($user);
    }

    public function assign(User $user, ItSupportTicket $ticket): bool
    {
        return $this->canOversee($user);
    }

    /** Active IT Leads and Assistants may access the restricted IT volume report. */
    public function viewVolumeReport(User $user): bool
    {
        return $this->canOversee($user);
    }

    /** Reuse this predicate to validate candidate assignees in controller workflows. */
    public function eligibleAssignee(User $actor, User $candidate): bool
    {
        return $this->canOversee($actor) && $this->isSupportTeam($candidate);
    }

    /**
     * IT Leads / Assistants work the queue; super admins and administrators
     * may oversee and manage it too. Tickets are only ever assigned to the
     * IT team (see eligibleAssignee / isSupportTeam).
     */
    private function canOversee(User $user): bool
    {
        return $this->isSupportTeam($user)
            || ($user->isActive() && ($user->isSuperAdmin() || $user->hasRole(['administrator', 'Administrator'])));
    }

    private function isSupportTeam(User $user): bool
    {
        return $user->isActive() && $user->hasRole([
            'it-lead', 'it-assistant', 'IT Lead', 'IT Assistant',
        ]);
    }
}
