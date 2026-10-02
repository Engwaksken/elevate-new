<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class ResumePortfolioFile extends Model
{
    protected $fillable = ['resume_id', 'label', 'original_name', 'path', 'file_size'];
    protected $hidden = ['path'];
    protected $appends = ['download_path'];

    public function resume() { return $this->belongsTo(Resume::class); }
    public function getDownloadPathAttribute(): string { return '/career/resumes/'.$this->resume_id.'/portfolio-files/'.$this->id; }
    public function shareUrl(): string { return URL::temporarySignedRoute('career.portfolio.shared', now()->addDays(7), ['file' => $this->id]); }
}
