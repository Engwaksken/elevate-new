<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AiIntegration extends Model {
    protected $fillable=['feature','provider','model','endpoint','encrypted_api_key','enabled','settings'];
    protected $casts=['enabled'=>'boolean','settings'=>'array'];
    public function setApiKey(?string $key):void { if($key) $this->encrypted_api_key=Crypt::encryptString($key); }
    public function apiKey():?string { try{return $this->encrypted_api_key?Crypt::decryptString($this->encrypted_api_key):null;}catch(\Throwable){return null;} }
}
