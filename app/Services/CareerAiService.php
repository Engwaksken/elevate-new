<?php

namespace App\Services;

use App\Models\AiIntegration;
use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Http;

class CareerAiService {
    public function generate(string $feature,string $system,string $user,?int $userId=null):array {
        $cfg=AiIntegration::where('feature',$feature)->where('enabled',true)->first()
            ?? AiIntegration::where('feature','career_ai')->where('enabled',true)->first();
        if(!$cfg || !$cfg->apiKey()) throw new \RuntimeException('AI_UNAVAILABLE');
        $started=microtime(true);
        try{
            $result=strtolower($cfg->provider)==='gemini'
                ? $this->gemini($cfg,$system,$user)
                : $this->openAi($cfg,$system,$user);
            AiUsageLog::create(['user_id'=>$userId,'feature'=>$feature,'provider'=>$cfg->provider,'model'=>$cfg->model,'status'=>'success',
                'input_tokens'=>$result['input_tokens']??0,'output_tokens'=>$result['output_tokens']??0,'duration_ms'=>(int)((microtime(true)-$started)*1000)]);
            return $result;
        }catch(\Throwable $e){
            report($e);
            AiUsageLog::create(['user_id'=>$userId,'feature'=>$feature,'provider'=>$cfg->provider,'model'=>$cfg->model,'status'=>'failed',
                'duration_ms'=>(int)((microtime(true)-$started)*1000),'error_code'=>class_basename($e)]);
            throw new \RuntimeException('AI_UNAVAILABLE');
        }
    }

    private function openAi(AiIntegration $c,string $system,string $user):array {
        $endpoint=$c->endpoint ?: 'https://api.openai.com/v1/chat/completions';
        $r=Http::timeout(60)->withToken($c->apiKey())->post($endpoint,[
            'model'=>$c->model ?: 'gpt-5.6',
            'messages'=>[['role'=>'system','content'=>$system],['role'=>'user','content'=>$user]],
            'temperature'=>$c->settings['temperature']??0.4,
        ])->throw()->json();
        return ['text'=>data_get($r,'choices.0.message.content',''),'input_tokens'=>(int)data_get($r,'usage.prompt_tokens',0),'output_tokens'=>(int)data_get($r,'usage.completion_tokens',0)];
    }

    private function gemini(AiIntegration $c,string $system,string $user):array {
        $model=$c->model ?: 'gemini-2.5-flash';
        $endpoint=$c->endpoint ?: "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
        $r=Http::timeout(60)->post($endpoint.'?key='.urlencode($c->apiKey()),[
            'system_instruction'=>['parts'=>[['text'=>$system]]],
            'contents'=>[['parts'=>[['text'=>$user]]]],
        ])->throw()->json();
        return ['text'=>data_get($r,'candidates.0.content.parts.0.text','')];
    }
}
