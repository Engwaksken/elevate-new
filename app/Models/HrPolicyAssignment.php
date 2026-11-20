<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A policy shared with one staff member, tracking when they read and acknowledged it. */
class HrPolicyAssignment extends Model
{
    protected $fillable = [
        'policy_id', 'user_id', 'assigned_by', 'due_date', 'viewed_at', 'acknowledged_at', 'reminders_sent', 'last_reminded_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'viewed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'last_reminded_at' => 'datetime',
    ];

    public function policy() { return $this->belongsTo(HrPolicy::class, 'policy_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
