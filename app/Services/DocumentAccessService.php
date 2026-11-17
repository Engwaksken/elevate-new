<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\DocumentShare;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Works out what a staff member may do in the department document libraries.
 *
 * Like SharePoint site groups:
 *  - Owners (full control): the department head and anyone with documents.manage
 *    (administrators get it through their role).
 *  - Members (edit): staff whose employee record sits in the department.
 *  - Anyone else only through an explicit share on the site, a folder or a
 *    file. A share on a folder carries down to everything inside it, and the
 *    highest grant wins.
 */
class DocumentAccessService
{
    /** @var array<int, array<string, string>> user id => ["type:id" => role] */
    private array $shareCache = [];
    /** @var array<int, array<int, string>> user id => [department id => role] */
    private array $siteCache = [];
    private array $managerCache = [];

    public static function rank(?string $role): int
    {
        return $role ? (DocumentShare::RANKS[$role] ?? 0) : 0;
    }

    public static function allows(?string $role, string $needed): bool
    {
        return self::rank($role) >= self::rank($needed);
    }

    public static function strongest(?string ...$roles): ?string
    {
        $best = null;
        foreach ($roles as $role) {
            if (self::rank($role) > self::rank($best)) $best = $role;
        }

        return $best;
    }

    public function isManager(User $user): bool
    {
        return $this->managerCache[$user->id] ??= $user->hasPermission('documents.manage');
    }

    /** Role a user holds on a department site through membership, headship or a site share. */
    public function siteRole(User $user, Department $department): ?string
    {
        return $this->siteRoles($user)[$department->id]
            ?? $this->directShare($user, DocumentShare::SITE, $department->id);
    }

    /** Role on a folder: the site role plus any share on the folder or a folder above it. */
    public function folderRole(User $user, DocumentFolder $folder): ?string
    {
        $role = $this->siteRole($user, $folder->department);
        foreach ($folder->ancestry() as $node) {
            $role = self::strongest($role, $this->directShare($user, DocumentShare::FOLDER, $node->id));
        }

        return $role;
    }

    public function fileRole(User $user, DocumentFile $file): ?string
    {
        $base = $file->folder ? $this->folderRole($user, $file->folder) : $this->siteRole($user, $file->department);

        return self::strongest($base, $this->directShare($user, DocumentShare::FILE, $file->id));
    }

    /** Role at a location in a library: the root (null folder) or a folder. */
    public function locationRole(User $user, Department $department, ?DocumentFolder $folder): ?string
    {
        return $folder ? $this->folderRole($user, $folder) : $this->siteRole($user, $department);
    }

    /** Role on a child row, given the role already held at its parent location. */
    public function childRole(User $user, ?string $locationRole, string $type, int $id): ?string
    {
        return self::strongest($locationRole, $this->directShare($user, $type, $id));
    }

    public function directShare(User $user, string $type, int $id): ?string
    {
        return $this->shares($user)[$type.':'.$id] ?? null;
    }

    /** Department sites the user can enter at the root, with their role. */
    public function accessibleSites(User $user): Collection
    {
        $query = Department::query()->orderBy('name');
        if (! $this->isManager($user)) {
            $ids = array_keys($this->siteRoles($user));
            foreach ($this->shares($user) as $key => $role) {
                [$type, $id] = explode(':', $key);
                if ($type === DocumentShare::SITE) $ids[] = (int) $id;
            }
            $query->whereIn('id', array_unique($ids) ?: [0]);
        } else {
            $query->where(fn ($q) => $q->where('is_active', true)->orWhereNull('is_active'));
        }

        return $query->get()->each(fn (Department $d) => $d->setAttribute('access_role', $this->siteRole($user, $d)));
    }

    /**
     * Folders and files shared directly with the user that they can't already
     * reach from a site they belong to.
     */
    public function sharedWithMe(User $user): Collection
    {
        $shares = DocumentShare::where('user_id', $user->id)
            ->whereIn('shareable_type', [DocumentShare::FOLDER, DocumentShare::FILE])
            ->with('grantor:id,name')
            ->latest()
            ->get();

        $folders = DocumentFolder::with(['department', 'editor:id,name'])
            ->whereIn('id', $shares->where('shareable_type', DocumentShare::FOLDER)->pluck('shareable_id'))->get()->keyBy('id');
        $files = DocumentFile::with(['department', 'editor:id,name', 'folder'])
            ->whereIn('id', $shares->where('shareable_type', DocumentShare::FILE)->pluck('shareable_id'))->get()->keyBy('id');

        return $shares->map(function (DocumentShare $share) use ($folders, $files, $user) {
            $item = $share->shareable_type === DocumentShare::FOLDER ? $folders->get($share->shareable_id) : $files->get($share->shareable_id);
            if (! $item || ! $item->department || $this->siteRole($user, $item->department)) return null;

            return (object) [
                'type' => $share->shareable_type,
                'item' => $item,
                'role' => $share->shareable_type === DocumentShare::FOLDER ? $this->folderRole($user, $item) : $this->fileRole($user, $item),
                'shared_by' => $share->grantor?->name,
                'shared_at' => $share->created_at,
            ];
        })->filter()->values();
    }

    /** @return array<int, string> department id => role from membership / headship / manager rights */
    private function siteRoles(User $user): array
    {
        if (isset($this->siteCache[$user->id])) return $this->siteCache[$user->id];

        $roles = [];
        if ($this->isManager($user)) {
            foreach (Department::pluck('id') as $id) $roles[$id] = DocumentShare::FULL;
        } else {
            foreach (Employee::where('user_id', $user->id)->whereNotNull('department_id')->pluck('department_id') as $id) {
                $roles[$id] = DocumentShare::EDIT;
            }
            foreach (Department::where('head_user_id', $user->id)->pluck('id') as $id) {
                $roles[$id] = DocumentShare::FULL;
            }
        }

        return $this->siteCache[$user->id] = $roles;
    }

    private function shares(User $user): array
    {
        return $this->shareCache[$user->id] ??= DocumentShare::where('user_id', $user->id)
            ->get(['shareable_type', 'shareable_id', 'role'])
            ->mapWithKeys(fn ($s) => [$s->shareable_type.':'.$s->shareable_id => $s->role])
            ->all();
    }

    /** Forget cached grants, e.g. after shares change within a request. */
    public function flush(): void
    {
        $this->shareCache = $this->siteCache = $this->managerCache = [];
    }
}
