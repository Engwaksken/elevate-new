<?php

namespace App\Services;

use App\Models\AiIntegration;
use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Http;

class CareerAiService {
    public function generate(string $feature,string $system,string $user,?int $userId=null):array {
        $cfg=AiIntegration::where('feature','system_ai')->where('enabled',true)->first();
        if(!$cfg || !$cfg->apiKey() || !$cfg->model) throw new \RuntimeException('AI_UNAVAILABLE');
        $usage = AiUsageLog::where('created_at', '>=', now()->startOfDay())->where('status', 'success');
        if ((clone $usage)->count() >= ($cfg->settings['daily_limit'] ?? 1000)
            || ($userId && (clone $usage)->where('user_id', $userId)->count() >= ($cfg->settings['user_daily_limit'] ?? 20))) throw new \RuntimeException('AI_UNAVAILABLE');
        $started=microtime(true);
        try{
            $provider = AiProviderRegistry::resolve(strtolower($cfg->provider));
            $result = match($provider['api']) {
                'gemini' => $this->gemini($cfg,$provider,$system,$user),
                'anthropic' => $this->anthropic($cfg,$provider,$system,$user),
                default => $this->openAi($cfg,$provider,$system,$user),
            };
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

    public function testConnection(AiIntegration $config): array
    {
        $provider = AiProviderRegistry::resolve(strtolower($config->provider));
        try {
            match($provider['api']) {
                'gemini' => $this->gemini($config, $provider, 'You are a concise assistant.', 'Reply with exactly: OK'),
                'anthropic' => $this->anthropic($config, $provider, 'You are a concise assistant.', 'Reply with exactly: OK'),
                default => $this->openAi($config, $provider, 'You are a concise assistant.', 'Reply with exactly: OK'),
            };
            return ['ok' => true, 'message' => 'Connected to '.$provider['label'].' — '.$config->model.' responded successfully.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Connection failed: '.$this->errorMessage($e)];
        }
    }

    private function errorMessage(\Throwable $e): string
    {
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return 'Could not reach the endpoint. Check the URL, model ID and your network.';
        }
        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            $json = $e->response?->json();
            if (is_array($json)) {
                $message = data_get($json, 'error.message') ?: data_get($json, 'error') ?: data_get($json, 'message');
                if (is_string($message) && $message !== '') return $message;
            }
        }
        return $e->getMessage() ?: class_basename($e);
    }

    private function openAi(AiIntegration $c,array $provider,string $system,string $user):array {
        $endpoint=$c->endpoint ?: ($provider['endpoint'] ?? 'https://api.openai.com/v1/chat/completions');
        $payload=[
            'model'=>$c->model,
            'messages'=>[['role'=>'system','content'=>$system],['role'=>'user','content'=>$user]],
            ...(isset($c->settings['temperature']) ? ['temperature' => (float) $c->settings['temperature']] : []),
        ];
        $http=Http::timeout(60);
        $auth=$provider['auth'] ?? 'bearer';
        if ($auth === 'api-key') $http=$http->withHeaders(['api-key'=>$c->apiKey()]);
        elseif ($auth !== 'none') $http=$http->withToken($c->apiKey());
        $r=$http->post($endpoint,$payload)->throw()->json();
        return ['text'=>data_get($r,'choices.0.message.content',''),'input_tokens'=>(int)data_get($r,'usage.prompt_tokens',0),'output_tokens'=>(int)data_get($r,'usage.completion_tokens',0)];
    }

    private function anthropic(AiIntegration $c,array $provider,string $system,string $user):array {
        $endpoint=$c->endpoint ?: ($provider['endpoint'] ?? 'https://api.anthropic.com/v1/messages');
        $payload=[
            'model'=>$c->model,
            'max_tokens'=>4096,
            'system'=>$system,
            'messages'=>[['role'=>'user','content'=>$user]],
            ...(isset($c->settings['temperature']) ? ['temperature' => (float) $c->settings['temperature']] : []),
        ];
        $r=Http::timeout(60)->withHeaders(['x-api-key'=>$c->apiKey(),'anthropic-version'=>'2023-06-01'])
            ->post($endpoint,$payload)->throw()->json();
        return ['text'=>data_get($r,'content.0.text',''),'input_tokens'=>(int)data_get($r,'usage.input_tokens',0),'output_tokens'=>(int)data_get($r,'usage.output_tokens',0)];
    }

    private function gemini(AiIntegration $c,array $provider,string $system,string $user):array {
        $model=$c->model ?: 'gemini-2.5-flash';
        $endpoint=$c->endpoint ?: ($provider['endpoint'] ?: "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent");
        $r=Http::timeout(60)->withHeaders(['x-goog-api-key' => $c->apiKey()])->post($endpoint,[
            'system_instruction'=>['parts'=>[['text'=>$system]]],
            'contents'=>[['parts'=>[['text'=>$user]]]],
        ])->throw()->json();
        return ['text'=>data_get($r,'candidates.0.content.parts.0.text','')];
    }
}
