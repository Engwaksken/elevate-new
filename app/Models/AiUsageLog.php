<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model {
    protected $fillable=['user_id','feature','provider','model','status','input_tokens','output_tokens','duration_ms','error_code','metadata'];
    protected $casts=['metadata'=>'array'];
}
