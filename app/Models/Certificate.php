<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'course_id','user_id','certificate_number','issued_on','pdf_path','verification_token'
    ];
    protected $casts = ['issued_on'=>'date'];
}
