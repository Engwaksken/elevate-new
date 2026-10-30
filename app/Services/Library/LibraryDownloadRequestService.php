<?php

namespace App\Services\Library;

use App\Models\LibraryDownloadRequest;
use App\Models\LibraryResource;
use App\Models\User;
use App\Services\UserNotificationService;

/** Download requests for "on request" books, shared by the website and the participant app. */
class LibraryDownloadRequestService
{
    public const ALREADY_ALLOWED = 'already_allowed';
    public const ALREADY_PENDING = 'already_pending';
    public const SENT = 'sent';

    /** Create a pending request and tell the library team, unless one isn't needed. */
    public function request(User $user, LibraryResource $resource, ?string $reason = null): string
    {
        if ($resource->canBeDownloadedBy($user)) {
            return self::ALREADY_ALLOWED;
        }

        $pending = LibraryDownloadRequest::where('user_id', $user->id)->where('library_resource_id', $resource->id)
            ->where('status', LibraryDownloadRequest::PENDING)->exists();
        if ($pending) {
            return self::ALREADY_PENDING;
        }

        LibraryDownloadRequest::create([
            'user_id' => $user->id,
            'library_resource_id' => $resource->id,
            'reason' => $reason,
            'status' => LibraryDownloadRequest::PENDING,
        ]);

        $librarians = User::query()->where('user_type', 'staff')->where('status', 'active')
            ->where(fn ($q) => $q->whereHas('roles.permissions', fn ($p) => $p->where('slug', 'library.manage'))
                ->orWhereHas('roles', fn ($r) => $r->whereIn('slug', ['super-administrator', 'super-admin', 'administrator'])))
            ->get();
        foreach ($librarians as $librarian) {
            app(UserNotificationService::class)->send($librarian, 'library_download_request', 'Library download request',
                $user->name.' asked to download "'.$resource->title.'".', route('admin.library.requests.index'));
        }

        return self::SENT;
    }
}
