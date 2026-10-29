<?php

namespace App\Services;

use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Account deletion requests and anonymisation.
 *
 * Completing a request removes personal data (identity, profile, private documents, messages,
 * reading history, notifications, devices, sessions) while keeping programme records such as
 * enrolments, certificates, attendance and feedback, which no longer identify the person.
 */
class AccountDeletionService
{
    /** Tables holding only the person's own private data, removed outright (user_id column). */
    private const PERSONAL_TABLES = [
        'career_action_items', 'career_mentor_messages', 'library_bookmarks', 'library_reading_progress',
        'library_download_requests', 'user_notifications', 'participant_device_tokens', 'participant_devices',
        'saved_jobs', 'participant_goals', 'cover_letters', 'cover_letter_uploads', 'resume_uploads', 'resumes',
        'mentee_profiles', 'profiles',
    ];

    /** Tables whose rows point at stored files (path column) to delete from the local disk first. */
    private const FILE_TABLES = ['resume_uploads', 'cover_letter_uploads'];

    public function __construct(private readonly UserNotificationService $notifications)
    {
    }

    public function request(User $user, ?string $reason, string $source): AccountDeletionRequest
    {
        $existing = AccountDeletionRequest::where('user_id', $user->id)->where('status', AccountDeletionRequest::PENDING)->first();
        if ($existing) {
            return $existing;
        }

        $request = AccountDeletionRequest::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'reason' => $reason ? Str::limit(trim($reason), 1000, '') : null,
            'source' => $source,
            'status' => AccountDeletionRequest::PENDING,
        ]);

        $admins = User::query()->where('user_type', 'staff')->where('status', 'active')
            ->where(fn ($q) => $q->whereHas('roles.permissions', fn ($p) => $p->where('slug', 'users.delete'))
                ->orWhereHas('roles', fn ($r) => $r->whereIn('slug', ['super-administrator', 'super-admin', 'administrator'])))
            ->get();
        foreach ($admins as $admin) {
            $this->notifications->send($admin, 'account_deletion_request', 'Account deletion request',
                $user->name.' asked for their account to be deleted.', route('admin.account-deletions.index'));
        }

        return $request;
    }

    public function complete(AccountDeletionRequest $request, User $admin, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $admin, $note) {
            if ($request->user) {
                $this->anonymise($request->user);
            }
            $request->update([
                'status' => AccountDeletionRequest::COMPLETED,
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'admin_note' => $note,
            ]);
        });
    }

    public function anonymise(User $user): void
    {
        $id = $user->id;

        // Stored files first, while the rows that point at them still exist.
        $disk = Storage::disk('local');
        foreach (self::FILE_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'path')) {
                DB::table($table)->where('user_id', $id)->pluck('path')->filter()->each(fn ($path) => $disk->delete($path));
            }
        }
        if (Schema::hasTable('resumes') && Schema::hasTable('resume_portfolio_files') && Schema::hasColumn('resume_portfolio_files', 'path')) {
            DB::table('resume_portfolio_files')->whereIn('resume_id', DB::table('resumes')->where('user_id', $id)->pluck('id'))
                ->pluck('path')->filter()->each(fn ($path) => $disk->delete($path));
        }
        $disk->deleteDirectory('profile-photos/'.$id);
        $disk->deleteDirectory('resumes/'.$id);
        $disk->deleteDirectory('cover-letters/'.$id);

        foreach (self::PERSONAL_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->where('user_id', $id)->delete();
            }
        }

        // Sign out everywhere (app tokens live in Sanctum's table, which is installed outside database/migrations).
        if (Schema::hasTable('personal_access_tokens')) {
            $user->tokens()->delete();
        }
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $id)->delete();
        }

        $user->forceFill([
            'name' => 'Deleted participant',
            'email' => 'deleted-'.$id.'-'.Str::lower(Str::random(8)).'@deleted.invalid',
            'phone' => null,
            'password' => Hash::make(Str::random(64)),
            // users.status is an enum (active/inactive/suspended/pending); inactive blocks sign-in.
            'status' => 'inactive',
            'remember_token' => null,
        ])->save();
    }
}
