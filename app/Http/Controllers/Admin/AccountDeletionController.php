<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Services\AccountDeletionService;
use App\Support\PeriodFilter;
use Illuminate\Http\Request;

class AccountDeletionController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), [AccountDeletionRequest::PENDING, AccountDeletionRequest::COMPLETED, AccountDeletionRequest::REJECTED], true)
            ? $request->query('status') : ($request->has('status') ? null : AccountDeletionRequest::PENDING);

        $query = AccountDeletionRequest::with('processor:id,name')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when(trim((string) $request->query('search')) !== '', function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('search')).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->latest();
        PeriodFilter::apply($query, 'created_at', $request);

        return view('admin.account-deletions.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'counts' => [
                'pending' => AccountDeletionRequest::where('status', AccountDeletionRequest::PENDING)->count(),
                'overdue' => AccountDeletionRequest::where('status', AccountDeletionRequest::PENDING)->where('created_at', '<', now()->subDays(AccountDeletionRequest::DAYS_TO_PROCESS - 5))->count(),
                'completed' => AccountDeletionRequest::where('status', AccountDeletionRequest::COMPLETED)->count(),
                'rejected' => AccountDeletionRequest::where('status', AccountDeletionRequest::REJECTED)->count(),
            ],
        ]);
    }

    public function complete(Request $request, AccountDeletionRequest $deletionRequest, AccountDeletionService $deletions)
    {
        abort_unless($deletionRequest->status === AccountDeletionRequest::PENDING, 422, 'This request has already been handled.');
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000'], 'confirm' => ['accepted']],
            ['confirm.accepted' => 'Tick the box to confirm the account should be deleted.']);

        $deletions->complete($deletionRequest, $request->user(), $data['note'] ?? null);

        return back()->with('success', 'Account deleted. Personal data was removed and programme records were anonymised.');
    }

    public function reject(Request $request, AccountDeletionRequest $deletionRequest)
    {
        abort_unless($deletionRequest->status === AccountDeletionRequest::PENDING, 422, 'This request has already been handled.');
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        $deletionRequest->update([
            'status' => AccountDeletionRequest::REJECTED,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
            'admin_note' => $data['note'],
        ]);

        if ($deletionRequest->user) {
            app(\App\Services\UserNotificationService::class)->send($deletionRequest->user, 'account_deletion_rejected', 'Account deletion request',
                'Your account deletion request was not completed. Reason: '.$data['note'], route('account-deletion.show'));
        }

        return back()->with('success', 'Request rejected and the participant was told why.');
    }
}
