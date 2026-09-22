<?php
namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    public function get(string $key, $default = null)
    {
        return Cache::remember("setting:{$key}",300,function() use($key,$default){
            $setting=SystemSetting::where('key',$key)->first();
            return $setting ? $setting->typed_value : $default;
        });
    }

    public function set(string $key, $value, string $type='string', string $group='general', bool $public=false, bool $encrypted=false): SystemSetting
    {
        $stored=$type==='json' ? json_encode($value) : (string)$value;

        if($encrypted && $stored!==''){
            $stored=Crypt::encryptString($stored);
        }

        $setting=SystemSetting::updateOrCreate(
            ['key'=>$key],
            [
                'value'=>$stored,
                'type'=>$type,
                'group'=>$group,
                'is_public'=>$public,
                'is_encrypted'=>$encrypted,
            ]
        );

        Cache::forget("setting:{$key}");

        return $setting;
    }
}
