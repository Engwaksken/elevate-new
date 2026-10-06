<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreStaffSupportTicketRequest;
use App\Models\ItSupportTicket;
use App\Services\ItSupportTicketService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Staff self-service Help & Support: any staff member raises IT / general help requests
 * and tracks only their own. The shared IT queue (it-support.tickets.*) stays for IT roles.
 */
class StaffSupportController extends Controller
{
    use ExportsTables;

    public function index(Request $request, SettingsService $settings)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(array_keys(ItSupportTicket::STATUSES))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $mine = ItSupportTicket::query()->where('requester_id', $request->user()->id);

        $query = (clone $mine)->with('assignee:id,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($w) => $w->where('subject', 'like', $term)->orWhere('description', 'like', $term));
            })
            ->latest();

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'My Help & Support Requests', $query, [
                'Reference' => fn ($t) => '#'.$t->id,
                'Subject' => 'subject',
                'Category' => fn ($t) => ItSupportTicket::categoryLabel($t->category),
                'Priority' => fn ($t) => ItSupportTicket::PRIORITIES[$t->priority] ?? ucfirst((string) $t->priority),
                'Status' => fn ($t) => ItSupportTicket::STATUSES[$t->status] ?? ucwords(str_replace('_', ' ', (string) $t->status)),
                'Assigned to' => 'assignee.name',
                'Submitted' => 'created_at',
                'Status Updated' => 'status_updated_at',
            ]);
        }

        $tickets = $query->paginate(15)->withQueryString();

        $counts = (clone $mine)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $stats = [
            'open' => (int) ($counts['open'] ?? 0),
            'in_progress' => (int) ($counts['in_progress'] ?? 0),
            'awaiting_requester' => (int) ($counts['awaiting_requester'] ?? 0),
            'resolved' => (int) ($counts['resolved'] ?? 0),
        ];

        $support = [
            'email' => $settings->get('support.email', ''),
            'phone' => $settings->get('support.phone', ''),
            'whatsapp' => $settings->get('support.whatsapp', ''),
            'hours' => $settings->get('support.hours', ''),
            'technical' => $settings->get('support.technical', ''),
        ];

        return view('staff.support.index', compact('tickets', 'stats', 'support'));
    }

    public function store(StoreStaffSupportTicketRequest $request, ItSupportTicketService $tickets): RedirectResponse
    {
        $ticket = $tickets->submit($request->user(), $request->validated(), $request);

        return redirect()->route('staff.support.index')
            ->with('success', "Your request #{$ticket->id} has been sent to the IT support team.");
    }
}
