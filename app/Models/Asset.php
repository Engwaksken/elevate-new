<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable=[
        'asset_code',
        'asset_tag',
        'asset_category_id',
        'description',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
        'purchase_price',
        'currency',
        'supplier_id',
        'purchase_order_id',
        'goods_receipt_id',
        'programme_id',
        'project_id',
        'funding_source',
        'location',
        'custodian_user_id',
        'condition',
        'warranty_end_date',
        'status',
        'notes',
    ];

    protected $casts=[
        'purchase_date'=>'date',
        'warranty_end_date'=>'date',
        'purchase_price'=>'decimal:2',
    ];

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function maintenance()
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    public function disposal()
    {
        return $this->hasOne(AssetDisposal::class);
    }
}
