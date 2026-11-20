<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A document a staff member submitted to HR. */
class StaffDocument extends Model
{
    public const DISK = 'local';

    protected $fillable = [
        'user_id', 'requirement_id', 'title', 'path', 'original_name', 'mime_type', 'size',
        'status', 'review_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $attributes = ['status' => 'submitted'];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function requirement() { return $this->belongsTo(HrDocumentRequirement::class, 'requirement_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
