<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationStagingRecord extends Model
{
    protected $fillable = [
        'migration_batch_id','source_table','source_record_id','entity_type',
        'source_payload','normalised_payload','match_status','matched_user_id',
        'validation_errors','processed_at'
    ];
    protected $casts = [
        'source_payload'=>'array','normalised_payload'=>'array','validation_errors'=>'array','processed_at'=>'datetime'
    ];
}
