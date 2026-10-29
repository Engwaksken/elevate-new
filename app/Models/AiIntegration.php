<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AiIntegration extends Model {
    /** System AI providers are stored one row per provider as "system_ai:{provider}"; "system_ai" is the legacy single row. */
    public const SYSTEM_FEATURE='system_ai';

    protected $fillable=['feature','provider','model','endpoint','encrypted_api_key','enabled','settings'];
    protected $casts=['enabled'=>'boolean','settings'=>'array'];
    protected $hidden=['encrypted_api_key'];
    public function setApiKey(?string $key):void { if($key) $this->encrypted_api_key=Crypt::encryptString($key); }
    public function apiKey():?string { try{return $this->encrypted_api_key?Crypt::decryptString($this->encrypted_api_key):null;}catch(\Throwable){return null;} }

    public function scopeSystemProviders(Builder $query):Builder {
        return $query->where(fn($q)=>$q->where('feature',self::SYSTEM_FEATURE)->orWhere('feature','like',self::SYSTEM_FEATURE.':%'));
    }

    /** The provider every AI feature uses, or null when AI is switched off. */
    public static function activeSystem():?self {
        return static::query()->systemProviders()->where('enabled',true)->latest('updated_at')->first();
    }

    public static function forProvider(string $provider):self {
        return static::firstOrNew(['feature'=>self::SYSTEM_FEATURE.':'.$provider],['provider'=>$provider]);
    }
}
