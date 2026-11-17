<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentFileVersion extends Model
{
    protected $fillable = ['document_file_id', 'version', 'path', 'size', 'mime_type', 'comment', 'uploaded_by'];
    protected $casts = ['size' => 'integer', 'version' => 'integer'];

    public function file(): BelongsTo { return $this->belongsTo(DocumentFile::class, 'document_file_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
