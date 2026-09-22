<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model
{
    protected $fillable=['name','category','registration_number','tin','contact_person','phone','email','address','bank_name','bank_account_name','bank_account_number_encrypted','status','performance_score','performance_notes'];
    protected $casts=['performance_score'=>'decimal:2','bank_account_number_encrypted'=>'encrypted'];
}
