<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkplanApproval extends Model
{
    protected $table = 'workplan_approvals';

    public $timestamps = false;

    protected $fillable = ['workplan_id', 'user_id', 'action', 'comments', 'acted_at'];

    protected $casts = ['acted_at' => 'datetime'];

    public function workplan(): BelongsTo
    {
        return $this->belongsTo(Workplan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
