<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class MobileSyncAction extends Model {
 protected $fillable=['user_id','client_action_id','action_type','payload','occurred_at','status','processed_at','error_message'];
 protected $casts=['payload'=>'array','occurred_at'=>'datetime','processed_at'=>'datetime'];
}
