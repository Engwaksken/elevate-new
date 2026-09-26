<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EventReminder extends Model {
    protected $fillable=['event_id','minutes_before','delivery_method','is_active','last_processed_at'];
    protected $casts=['is_active'=>'boolean','last_processed_at'=>'datetime'];
    public function event(){ return $this->belongsTo(Event::class); }
}