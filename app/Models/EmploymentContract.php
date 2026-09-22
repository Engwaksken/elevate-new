<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmploymentContract extends Model
{
    protected $fillable=['employee_id','contract_type','start_date','end_date','gross_salary','currency','document_path','status'];
    protected $casts=['start_date'=>'date','end_date'=>'date','gross_salary'=>'decimal:2'];
}
