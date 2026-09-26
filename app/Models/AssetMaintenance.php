<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends Model
{
    protected $table='asset_maintenance';

    protected $fillable=[
        'asset_id',
        'reported_date',
        'maintenance_type',
        'issue_description',
        'supplier_id',
        'cost',
        'currency',
        'completed_date',
        'resolution',
        'status',
    ];

    protected $casts=[
        'reported_date'=>'date',
        'completed_date'=>'date',
        'cost'=>'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
