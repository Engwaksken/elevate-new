<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryDownloadRequest;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;

class LibraryDownloadRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), [LibraryDownloadRequest::PENDING, LibraryDownloadRequest::APPROVED, LibraryDownloadRequest::DECLINED], true)
            ? $request->query('status') : ($request->has('status') ? null : LibraryDownloadRequest::PENDING);

        $requests = LibraryDownloadRequest::with(['user:id,name,email', 'resource:id,title,author', 'decider:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when(trim((string) $request->query('search')) !== '', function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('search')).'%';
                $q->where(fn ($w) => $w->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('resource', fn ($r) => $r->where('title', 'like', $term)));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.library.requests', [
            'requests' => $requests,
            'status' => $status,
            'counts' => [
                'pending' => LibraryDownloadRequest::where('status', LibraryDownloadRequest::PENDING)->count(),
                'approved' => LibraryDownloadRequest::where('status', LibraryDownloadRequest::APPROVED)->count(),
                'declined' => LibraryDownloadRequest::where('status', LibraryDownloadRequest::DECLINED)->count(),
            ],
        ]);
    }

    public function approve(Request $request, LibraryDownloadRequest $downloadRequest, UserNotificationService $notifications)
    {
        abort_unless($downloadRequest->status === LibraryDownloadRequest::PENDING, 422, 'This request has already been answered.');
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $downloadRequest->update([
            'status' => LibraryDownloadRequest::APPROVED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $data['note'] ?? null,
            'expires_at' => now()->addDays(LibraryDownloadRequest::APPROVAL_DAYS),
        ]);

        if ($downloadRequest->user && $downloadRequest->resource) {
            $notifications->send($downloadRequest->user, 'library_download_approved', 'You can now download a book',
                'Your request to download "'.$downloadRequest->resource->title.'" was approved. The download is available until '.$downloadRequest->expires_at->format('d M Y').'.'
                    .($downloadRequest->decision_note ? ' Note: '.$downloadRequest->decision_note : ''),
                route('library.show', $downloadRequest->library_resource_id));
        }

        return back()->with('success', 'Download approved for '.$downloadRequest->user?->name.'.');
    }

    public function decline(Request $request, LibraryDownloadRequest $downloadRequest, UserNotificationService $notifications)
    {
        abort_unless($downloadRequest->status === LibraryDownloadRequest::PENDING, 422, 'This request has already been answered.');
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        $downloadRequest->update([
            'status' => LibraryDownloadRequest::DECLINED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $data['note'],
        ]);

        if ($downloadRequest->user && $downloadRequest->resource) {
            $notifications->send($downloadRequest->user, 'library_download_declined', 'Download request not approved',
                'Your request to download "'.$downloadRequest->resource->title.'" was not approved. Reason: '.$data['note'].' You can still read it online.',
                route('library.show', $downloadRequest->library_resource_id));
        }

        return back()->with('success', 'Request declined.');
    }
}
