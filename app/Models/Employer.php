<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    protected $fillable = [
        'owner_user_id','company_name','company_type','industry','website','contact_person',
        'email','phone','country','location','description','logo_path','status','approved_at','approved_by'
    ];
    protected $casts = ['approved_at'=>'datetime'];

    public function owner(){ return $this->belongsTo(User::class,'owner_user_id'); }
    public function jobs(){ return $this->hasMany(Job::class); }
}
