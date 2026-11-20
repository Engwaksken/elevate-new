<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An organisation policy HR shares with staff, optionally requiring acknowledgement. */
class HrPolicy extends Model
{
    public const DISK = 'local';

    public const CATEGORIES = [
        'Code of Conduct', 'Safeguarding', 'Human Resources', 'Finance',
        'ICT & Data Protection', 'Health & Safety', 'Procurement', 'Other',
    ];

    protected $fillable = [
        'title', 'category', 'version', 'summary', 'body', 'file_path', 'file_name', 'effective_date',
        'requires_acknowledgement', 'assign_to_new_staff', 'due_days', 'is_active', 'created_by',
    ];

    protected $attributes = ['requires_acknowledgement' => true, 'assign_to_new_staff' => true, 'due_days' => 7, 'is_active' => true];

    protected $casts = [
        'effective_date' => 'date',
        'requires_acknowledgement' => 'boolean',
        'assign_to_new_staff' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function assignments() { return $this->hasMany(HrPolicyAssignment::class, 'policy_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
