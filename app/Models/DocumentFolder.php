<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentFolder extends Model
{
    protected $fillable = ['department_id', 'parent_id', 'name', 'description', 'created_by', 'updated_by'];

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function files(): HasMany { return $this->hasMany(DocumentFile::class, 'folder_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class, 'shareable_id')->where('shareable_type', DocumentShare::FOLDER);
    }

    /** Folders from the library root down to (and including) this one. */
    public function ancestry(): array
    {
        $chain = [$this];
        $seen = [$this->id => true];
        $node = $this;
        while ($node->parent_id && ! isset($seen[$node->parent_id])) {
            $node = $node->parent;
            if (! $node) break;
            $seen[$node->id] = true;
            array_unshift($chain, $node);
        }

        return $chain;
    }

    /** IDs of this folder and every folder below it. */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];
        while ($frontier) {
            $frontier = self::whereIn('parent_id', $frontier)->whereNotIn('id', $ids)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }
}
