<?php

namespace App\Services;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\DocumentShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/** Recycle bin, move and zip download for the department document libraries. */
class DocumentLibraryService
{
    public const DISK = 'local';

    /** Largest total a zip download may hold (500 MB). */
    public const MAX_ZIP_BYTES = 524288000;

    /* ---------------------------- Recycle bin ---------------------------- */

    /** Send an item (and everything inside a folder) to the recycle bin as one batch. */
    public function trash(DocumentFolder|DocumentFile $item, User $user): void
    {
        $batch = (string) Str::uuid();
        $stamp = ['deleted_by' => $user->id, 'delete_batch' => $batch, 'deleted_at' => now(), 'is_delete_root' => false];

        DB::transaction(function () use ($item, $stamp) {
            if ($item instanceof DocumentFolder) {
                $ids = $item->descendantIds();
                DocumentFile::whereIn('folder_id', $ids)->update($stamp);
                DocumentFolder::whereIn('id', $ids)->update($stamp);
                DocumentFolder::withTrashed()->whereKey($item->id)->update(['is_delete_root' => true]);
            } else {
                DocumentFile::whereKey($item->id)->update(['is_delete_root' => true] + $stamp);
            }
        });
    }

    /**
     * Bring a recycle-bin item back with everything deleted alongside it. If
     * its original folder is gone, it lands in the library root; a name clash
     * gets " (restored)" added.
     */
    public function restore(DocumentFolder|DocumentFile $root, User $user): void
    {
        DB::transaction(function () use ($root, $user) {
            $batch = $root->delete_batch;
            $parentColumn = $root instanceof DocumentFolder ? 'parent_id' : 'folder_id';
            $parentId = $root->{$parentColumn};
            if ($parentId && ! DocumentFolder::whereKey($parentId)->exists()) {
                $parentId = null;
            }

            $clear = ['deleted_at' => null, 'deleted_by' => null, 'delete_batch' => null, 'is_delete_root' => false];
            DocumentFolder::onlyTrashed()->where('delete_batch', $batch)->update($clear);
            DocumentFile::onlyTrashed()->where('delete_batch', $batch)->update($clear);

            $root->refresh();
            $name = $this->freeName($root::class, $root->department_id, $parentId, $root->name, $root->id, $root instanceof DocumentFile ? $root->extension : null);
            $root->forceFill([$parentColumn => $parentId, 'name' => $name, 'updated_by' => $user->id])->save();
        });
    }

    /** Remove a recycle-bin item (and its batch) for good, files included. */
    public function purge(DocumentFolder|DocumentFile $root): void
    {
        DB::transaction(function () use ($root) {
            $batch = $root->delete_batch;
            $files = $batch
                ? DocumentFile::onlyTrashed()->where('delete_batch', $batch)->get()
                : collect([$root])->filter(fn ($i) => $i instanceof DocumentFile);
            foreach ($files as $file) $this->purgeFile($file);

            $folderIds = $batch ? DocumentFolder::onlyTrashed()->where('delete_batch', $batch)->pluck('id')->all() : [];
            if ($folderIds) {
                DocumentShare::where('shareable_type', DocumentShare::FOLDER)->whereIn('shareable_id', $folderIds)->delete();
                // Children first so the parent_id foreign key never blocks.
                DocumentFolder::onlyTrashed()->whereIn('id', $folderIds)->orderByDesc('id')->get()->each->forceDelete();
            }
        });
    }

    public function purgeFile(DocumentFile $file): void
    {
        $paths = $file->versions()->pluck('path')->push($file->path)->unique()->all();
        Storage::disk(self::DISK)->delete($paths);
        DocumentShare::where('shareable_type', DocumentShare::FILE)->where('shareable_id', $file->id)->delete();
        $file->forceDelete();
    }

    /** Purge everything that has sat in the recycle bin past the retention period. */
    public function purgeExpired(): int
    {
        $cutoff = now()->subDays(DocumentFolder::RETENTION_DAYS);
        $count = 0;
        foreach ([DocumentFile::class, DocumentFolder::class] as $class) {
            $class::onlyTrashed()->where('is_delete_root', true)->where('deleted_at', '<', $cutoff)->get()
                ->each(function ($item) use (&$count) { $this->purge($item); $count++; });
        }

        return $count;
    }

    /* -------------------------------- Move ------------------------------- */

    /** Move folders and files into a folder (or the root) of the same library. */
    public function move(Collection $items, int $departmentId, ?DocumentFolder $destination, User $user): void
    {
        DB::transaction(function () use ($items, $departmentId, $destination, $user) {
            foreach ($items as $item) {
                if ((int) $item->department_id !== $departmentId) {
                    throw ValidationException::withMessages(['items' => 'Items can only be moved within the same library.']);
                }
                if ($item instanceof DocumentFolder && $destination && in_array($destination->id, $item->descendantIds(), true)) {
                    throw ValidationException::withMessages(['items' => "\"{$item->name}\" can't be moved into itself."]);
                }
                $column = $item instanceof DocumentFolder ? 'parent_id' : 'folder_id';
                if ((string) $item->{$column} === (string) $destination?->id) {
                    continue; // already there
                }
                $taken = $item::where('department_id', $departmentId)->where($column, $destination?->id)
                    ->whereRaw('lower(name) = ?', [mb_strtolower($item->name)])->where('id', '!=', $item->id)->exists();
                if ($taken) {
                    throw ValidationException::withMessages(['items' => "\"{$item->name}\" already exists in the destination folder."]);
                }
                $item->forceFill([$column => $destination?->id, 'updated_by' => $user->id])->save();
            }
        });
    }

    /* -------------------------------- Zip -------------------------------- */

    /**
     * Build a zip of files and folders (folders keep their structure).
     *
     * @return string absolute path of a temporary zip file
     */
    public function zip(Collection $items, string $label): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['items' => 'Zip downloads are not available on this server.']);
        }

        $entries = [];
        foreach ($items as $item) {
            if ($item instanceof DocumentFile) {
                $entries[] = [$item, $item->name];
            } else {
                $this->collectFolder($item, $item->name, $entries);
            }
        }

        $total = array_sum(array_map(fn ($e) => $e[0]->size, $entries));
        if ($total > self::MAX_ZIP_BYTES) {
            throw ValidationException::withMessages(['items' => 'That selection is larger than 500 MB. Download fewer items at a time.']);
        }

        $dir = storage_path('app/tmp');
        if (! is_dir($dir)) mkdir($dir, 0775, true);
        $path = $dir.'/'.Str::slug($label ?: 'documents').'-'.Str::random(8).'.zip';

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $used = [];
        foreach ($entries as [$file, $name]) {
            if (! Storage::disk(self::DISK)->exists($file->path)) continue;
            $name = $this->uniqueEntry($name, $used);
            $zip->addFile(Storage::disk(self::DISK)->path($file->path), $name);
        }
        if (! $entries) $zip->addEmptyDir($label ?: 'documents');
        foreach ($items as $item) {
            if ($item instanceof DocumentFolder) $zip->addEmptyDir($item->name);
        }
        $zip->close();

        return $path;
    }

    private function collectFolder(DocumentFolder $folder, string $prefix, array &$entries, int $depth = 0): void
    {
        if ($depth > 30) return;
        foreach ($folder->files()->get() as $file) $entries[] = [$file, $prefix.'/'.$file->name];
        foreach ($folder->children()->get() as $child) $this->collectFolder($child, $prefix.'/'.$child->name, $entries, $depth + 1);
    }

    private function uniqueEntry(string $name, array &$used): string
    {
        $candidate = $name;
        $i = 1;
        while (isset($used[mb_strtolower($candidate)])) {
            $info = pathinfo($name);
            $base = ($info['dirname'] !== '.' ? $info['dirname'].'/' : '').$info['filename'];
            $candidate = $base.' ('.$i++.')'.(isset($info['extension']) ? '.'.$info['extension'] : '');
        }
        $used[mb_strtolower($candidate)] = true;

        return $candidate;
    }

    private function freeName(string $class, int $departmentId, ?int $parentId, string $name, int $ignoreId, ?string $ext): string
    {
        $column = $class === DocumentFolder::class ? 'parent_id' : 'folder_id';
        $taken = fn (string $n) => $class::where('department_id', $departmentId)->where($column, $parentId)
            ->whereRaw('lower(name) = ?', [mb_strtolower($n)])->where('id', '!=', $ignoreId)->exists();
        if (! $taken($name)) return $name;

        $base = $ext ? Str::beforeLast($name, '.'.$ext) : $name;
        $suffix = $ext ? '.'.$ext : '';
        $candidate = "{$base} (restored){$suffix}";
        $i = 2;
        while ($taken($candidate)) {
            $candidate = "{$base} (restored {$i}){$suffix}";
            $i++;
        }

        return $candidate;
    }
}
