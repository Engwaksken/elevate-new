<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplicationStatusHistory extends Model
{
    protected $table = 'job_application_status_history';

    public $timestamps = false;

    protected $fillable = [
        'job_application_id', 'status', 'notes', 'changed_by', 'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
