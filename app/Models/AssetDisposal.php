<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDisposal extends Model
{
    protected $fillable=[
        'asset_id',
        'requested_date',
        'reason',
        'disposal_method',
        'disposal_value',
        'currency',
        'requested_by',
        'approved_by',
        'approved_at',
        'completed_at',
        'status',
    ];

    protected $casts=[
        'requested_date'=>'date',
        'disposal_value'=>'decimal:2',
        'approved_at'=>'datetime',
        'completed_at'=>'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
