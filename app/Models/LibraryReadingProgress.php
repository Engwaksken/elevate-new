<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryReadingProgress extends Model
{
    protected $table = 'library_reading_progress';

    protected $fillable = [
        'user_id', 'library_resource_id', 'current_page', 'total_pages', 'percent',
        'opened_count', 'first_opened_at', 'last_read_at', 'completed_at',
    ];

    protected $casts = [
        'percent' => 'decimal:2',
        'first_opened_at' => 'datetime',
        'last_read_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(LibraryResource::class, 'library_resource_id')->withTrashed();
    }
}
