<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ResumeCertification extends Model
{
    protected $fillable=['resume_id','name','issuer','issued_on','expires_on','credential_url'];
    protected $casts=['issued_on'=>'date','expires_on'=>'date'];
}
