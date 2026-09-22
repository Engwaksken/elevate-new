<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationBatch extends Model
{
    protected $fillable = [
        'source_system','batch_name','source_file','total_rows','processed_rows',
        'successful_rows','failed_rows','status','created_by'
    ];

    public function records(){ return $this->hasMany(MigrationStagingRecord::class); }
}
