<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformBackup extends Model
{
    protected $fillable = [
        'destination','filename','path','size_bytes','status',
        'error_message','created_by','completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
