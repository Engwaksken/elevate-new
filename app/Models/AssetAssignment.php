<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    protected $fillable=[
        'asset_id',
        'assigned_to_user_id',
        'assigned_date',
        'expected_return_date',
        'returned_date',
        'assignment_notes',
        'return_condition',
        'assigned_by',
        'received_back_by',
        'status',
    ];

    protected $casts=[
        'assigned_date'=>'date',
        'expected_return_date'=>'date',
        'returned_date'=>'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class,'assigned_to_user_id');
    }
}
