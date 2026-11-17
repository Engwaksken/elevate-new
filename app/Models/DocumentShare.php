<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentShare extends Model
{
    public const SITE = 'department';
    public const FOLDER = 'folder';
    public const FILE = 'file';

    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const FULL = 'full';

    /** Higher rank = more access. */
    public const RANKS = [self::VIEW => 1, self::EDIT => 2, self::FULL => 3];

    public const LABELS = [
        self::VIEW => 'Can view',
        self::EDIT => 'Can edit',
        self::FULL => 'Full control',
    ];

    public const DESCRIPTIONS = [
        self::VIEW => 'Open and download only.',
        self::EDIT => 'Upload, rename, create folders and add new versions.',
        self::FULL => 'Everything, including delete and managing access.',
    ];

    protected $fillable = ['shareable_type', 'shareable_id', 'user_id', 'role', 'granted_by'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function grantor(): BelongsTo { return $this->belongsTo(User::class, 'granted_by'); }

    public function label(): string
    {
        return self::LABELS[$this->role] ?? ucfirst($this->role);
    }
}
