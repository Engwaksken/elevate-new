<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class AppraisalStatusHistory extends Model {protected $table='appraisal_status_history'; protected $fillable=['appraisal_id','from_status','to_status','comment','changed_by','ip_address'];}
