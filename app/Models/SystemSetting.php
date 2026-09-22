<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    protected $fillable=['group','key','value','type','is_public','is_encrypted'];
    protected $casts=['is_public'=>'boolean','is_encrypted'=>'boolean'];

    public function getTypedValueAttribute()
    {
        $value=$this->is_encrypted && $this->value ? Crypt::decryptString($this->value) : $this->value;

        return match($this->type){
            'boolean' => filter_var($value,FILTER_VALIDATE_BOOLEAN),
            'integer' => (int)$value,
            'float' => (float)$value,
            'json' => $value ? json_decode($value,true) : null,
            default => $value,
        };
    }
}
