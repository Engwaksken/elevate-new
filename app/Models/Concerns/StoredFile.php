<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Helpers shared by models that describe one stored file
 * (columns: original_name, disk, path, mime_type, size_bytes).
 */
trait StoredFile
{
    public function diskName(): string
    {
        return $this->disk ?: 'local';
    }

    public function displayName(): string
    {
        return (string) ($this->original_name ?: ($this->stored_name ?? null) ?: basename((string) $this->path));
    }

    public function extension(): string
    {
        $extension = strtolower(pathinfo($this->displayName(), PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : strtolower(pathinfo((string) $this->path, PATHINFO_EXTENSION));
    }

    public function existsOnDisk(): bool
    {
        if (! $this->path) {
            return false;
        }

        try {
            return Storage::disk($this->diskName())->exists($this->path);
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteStoredFile(): void
    {
        if ($this->existsOnDisk()) {
            Storage::disk($this->diskName())->delete($this->path);
        }
    }
}
