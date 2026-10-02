<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ParticipantIdAlias extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'alias'];
}
