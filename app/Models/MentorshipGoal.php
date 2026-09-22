<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentorshipGoal extends Model
{
    protected $fillable = [
        'mentor_match_id','title','description','target_date','progress_percent','status'
    ];
    protected $casts = ['target_date'=>'date'];
}
