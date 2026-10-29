<?php

namespace App\Services;

use App\Models\AiIntegration;
use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Http;

class CareerAiService {
    /**
     * @param  array<int,array{role:string,content:string}>  $history  earlier user/assistant turns, oldest first
     */
    public function generate(string $feature,string $system,string $user,?int $userId=null,array $history=[]):array {
        $history=$this->normaliseHistory($history);
        $cfg=AiIntegration::where('feature','system_ai')->where('enabled',true)->first();
        if(!$cfg || !$cfg->apiKey() || !$cfg->model) throw new \RuntimeException('AI_UNAVAILABLE');
        $usage = AiUsageLog::where('created_at', '>=', now()->startOfDay())->where('status', 'success');
        if ((clone $usage)->count() >= ($cfg->settings['daily_limit'] ?? 1000)
            || ($userId && (clone $usage)->where('user_id', $userId)->count() >= ($cfg->settings['user_daily_limit'] ?? 20))) throw new \RuntimeException('AI_UNAVAILABLE');
        $started=microtime(true);
        try{
            $provider = AiProviderRegistry::resolve(strtolower($cfg->provider));
            $result = match($provider['api']) {
                'gemini' => $this->gemini($cfg,$provider,$system,$user,$history),
                'anthropic' => $this->anthropic($cfg,$provider,$system,$user,$history),
                default => $this->openAi($cfg,$provider,$system,$user,$history),
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
            return ['ok' => true, 'message' => 'Connected to '.$provider['label'].' - '.$config->model.' responded successfully.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Connection failed: '.$this->errorMessage($e, $config, $provider)];
        }
    }

    private function errorMessage(\Throwable $e, ?AiIntegration $config = null, array $provider = []): string
    {
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return 'Could not reach the endpoint. Check the URL, model ID and your network.';
        }
        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            $json = $e->response?->json();
            $message = null;
            if (is_array($json)) {
                $message = data_get($json, 'error.message') ?: data_get($json, 'error') ?: data_get($json, 'message') ?: data_get($json, 'detail');
            }
            $message = is_string($message) && $message !== '' ? $message : null;

            // A 404 means the endpoint URL or the model ID does not exist; say which ones were used.
            if ($e->response?->status() === 404 && $config) {
                $hint = 'The provider returned 404 (not found) for model "'.$config->model.'" at '.$this->endpointFor($config, $provider)
                    .'. Check that the model ID is current and available to your API key, and leave Endpoint blank to use the provider default.';
                return $message ? $message.' - '.$hint : $hint;
            }
            if ($message) return $message;
        }
        return $e->getMessage() ?: class_basename($e);
    }

    /**
     * Resolve the request URL, completing a base URL (e.g. https://api.openai.com/v1)
     * with the API path the provider expects, since a bare base URL returns 404.
     */
    private function endpointFor(AiIntegration $c, array $provider): string
    {
        $api = $provider['api'] ?? 'openai';
        $endpoint = rtrim(trim((string) $c->endpoint), '/');

        if ($api === 'gemini') {
            $model = $c->model ?: 'gemini-3.8-flash';
            if ($endpoint === '') {
                return $provider['endpoint'] ?: "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
            }
            if (str_contains($endpoint, ':generateContent')) return $endpoint;
            return preg_match('#/models$#', $endpoint) ? "{$endpoint}/{$model}:generateContent" : "{$endpoint}/models/{$model}:generateContent";
        }

        if ($endpoint === '') {
            return $provider['endpoint'] ?? ($api === 'anthropic' ? 'https://api.anthropic.com/v1/messages' : 'https://api.openai.com/v1/chat/completions');
        }

        if ($api === 'anthropic') {
            if (str_ends_with($endpoint, '/messages')) return $endpoint;
            return preg_match('#/v\d+$#', $endpoint) ? "{$endpoint}/messages" : "{$endpoint}/v1/messages";
        }

        // Azure deployment URLs carry the path and api-version query themselves.
        if (str_contains($endpoint, '?') || str_ends_with($endpoint, '/chat/completions')) return $endpoint;

        return "{$endpoint}/chat/completions";
    }

    /**
     * Keep only well-formed user/assistant turns that start with a user turn and
     * alternate roles (consecutive turns are merged) so every provider accepts them.
     */
    private function normaliseHistory(array $history):array {
        $turns=[];
        foreach($history as $entry){
            if(!is_array($entry)) continue;
            $role=strtolower((string)($entry['role']??''));
            $content=trim((string)($entry['content']??''));
            if(!in_array($role,['user','assistant'],true) || $content==='') continue;
            if($turns===[] && $role!=='user') continue;
            $last=array_key_last($turns);
            if($last!==null && $turns[$last]['role']===$role){
                $turns[$last]['content'].="\n\n".$content;
                continue;
            }
            $turns[]=['role'=>$role,'content'=>$content];
        }
        // The new question is a user turn, so history must end on an assistant turn.
        if($turns!==[] && end($turns)['role']==='user') array_pop($turns);
        return $turns;
    }

    private function openAi(AiIntegration $c,array $provider,string $system,string $user,array $history=[]):array {
        $endpoint=$this->endpointFor($c,$provider);
        $payload=[
            'model'=>$c->model,
            'messages'=>[['role'=>'system','content'=>$system],...$history,['role'=>'user','content'=>$user]],
            ...(isset($c->settings['temperature']) ? ['temperature' => (float) $c->settings['temperature']] : []),
        ];
        $http=Http::timeout(60);
        $auth=$provider['auth'] ?? 'bearer';
        if ($auth === 'api-key') $http=$http->withHeaders(['api-key'=>$c->apiKey()]);
        elseif ($auth !== 'none') $http=$http->withToken($c->apiKey());
        $r=$http->post($endpoint,$payload)->throw()->json();
        return ['text'=>data_get($r,'choices.0.message.content',''),'input_tokens'=>(int)data_get($r,'usage.prompt_tokens',0),'output_tokens'=>(int)data_get($r,'usage.completion_tokens',0)];
    }

    private function anthropic(AiIntegration $c,array $provider,string $system,string $user,array $history=[]):array {
        $endpoint=$this->endpointFor($c,$provider);
        // Current Claude models reject sampling parameters such as temperature (HTTP 400).
        $sendTemperature=isset($c->settings['temperature']) && !str_starts_with(strtolower((string)$c->model),'claude-');
        $payload=[
            'model'=>$c->model,
            'max_tokens'=>4096,
            'system'=>$system,
            'messages'=>[...$history,['role'=>'user','content'=>$user]],
            ...($sendTemperature ? ['temperature' => (float) $c->settings['temperature']] : []),
        ];
        $r=Http::timeout(60)->withHeaders(['x-api-key'=>$c->apiKey(),'anthropic-version'=>'2023-06-01'])
            ->post($endpoint,$payload)->throw()->json();
        // Responses can start with thinking blocks, so join every text block rather than reading content[0].
        $text=collect(data_get($r,'content',[]))->where('type','text')->pluck('text')->implode('');
        return ['text'=>$text,'input_tokens'=>(int)data_get($r,'usage.input_tokens',0),'output_tokens'=>(int)data_get($r,'usage.output_tokens',0)];
    }

    private function gemini(AiIntegration $c,array $provider,string $system,string $user,array $history=[]):array {
        $endpoint=$this->endpointFor($c,$provider);
        $r=Http::timeout(60)->withHeaders(['x-goog-api-key' => $c->apiKey()])->post($endpoint,[
            'system_instruction'=>['parts'=>[['text'=>$system]]],
            'contents'=>[
                ...array_map(fn($t)=>['role'=>$t['role']==='assistant'?'model':'user','parts'=>[['text'=>$t['content']]]],$history),
                ['role'=>'user','parts'=>[['text'=>$user]]],
            ],
        ])->throw()->json();
        return ['text'=>data_get($r,'candidates.0.content.parts.0.text','')];
    }
}
