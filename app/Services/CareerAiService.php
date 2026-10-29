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
        $cfg=AiIntegration::activeSystem();
        if(!$cfg || !$cfg->apiKey() || !$cfg->model) throw new \RuntimeException('AI_UNAVAILABLE');
        $usage = AiUsageLog::where('created_at', '>=', now()->startOfDay())->where('status', 'success');
        $limit = match (true) {
            (clone $usage)->count() >= ($cfg->settings['daily_limit'] ?? 1000) => 'Daily system request limit reached.',
            $userId && (clone $usage)->where('user_id', $userId)->count() >= ($cfg->settings['user_daily_limit'] ?? 20) => 'Daily request limit per user reached.',
            default => null,
        };
        if ($limit) {
            AiUsageLog::create(['user_id'=>$userId,'feature'=>$feature,'provider'=>$cfg->provider,'model'=>$cfg->model,'status'=>'limited','metadata'=>['error'=>$limit]]);
            throw new \RuntimeException('AI_UNAVAILABLE');
        }
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
            // Keep the readable reason so admins can see why answers fell back to offline guidance.
            AiUsageLog::create(['user_id'=>$userId,'feature'=>$feature,'provider'=>$cfg->provider,'model'=>$cfg->model,'status'=>'failed',
                'duration_ms'=>(int)((microtime(true)-$started)*1000),'error_code'=>class_basename($e),
                'metadata'=>['error'=>mb_substr($this->errorMessage($e,$cfg,$provider??[]),0,500)]]);
            throw new \RuntimeException('AI_UNAVAILABLE');
        }
    }

    /**
     * Fail loudly when a provider answers 200 but without usable text (blocked, cut off, empty).
     */
    private function requireText(array $result, ?string $reason = null): array
    {
        if (trim((string) ($result['text'] ?? '')) === '') {
            throw new \UnexpectedValueException('The model returned no text'.($reason ? ' (finish reason: '.$reason.')' : '').'.');
        }
        return $result;
    }

    public function testConnection(AiIntegration $config): array
    {
        $provider = AiProviderRegistry::resolve(strtolower($config->provider));
        try {
            // Ask a realistic question so the test exercises the same path as real answers.
            $result = match($provider['api']) {
                'gemini' => $this->gemini($config, $provider, 'You are a concise career assistant.', 'In one sentence, give one tip for choosing a career path.'),
                'anthropic' => $this->anthropic($config, $provider, 'You are a concise career assistant.', 'In one sentence, give one tip for choosing a career path.'),
                default => $this->openAi($config, $provider, 'You are a concise career assistant.', 'In one sentence, give one tip for choosing a career path.'),
            };
            return ['ok' => true, 'message' => 'Connected to '.$provider['label'].' - '.$config->model.' answered: "'.\Illuminate\Support\Str::limit(trim($result['text']), 160).'"'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Connection failed: '.$this->errorMessage($e, $config, $provider)];
        }
    }

    private function errorMessage(\Throwable $e, ?AiIntegration $config = null, array $provider = []): string
    {
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            // Surface the cURL reason (timeout, SSL certificate, DNS, blocked outbound port) so it can be acted on.
            $reason = preg_replace('/\s*\(see https?:\/\/curl\.haxx\.se[^)]*\)/', '', $e->getMessage());
            $hint = match (true) {
                str_contains($reason, 'cURL error 28') => ' The provider did not answer in time; try again or pick a faster model.',
                str_contains($reason, 'cURL error 60'), str_contains($reason, 'cURL error 77') => ' The server cannot verify SSL certificates: set curl.cainfo/openssl.cafile in php.ini to a current cacert.pem.',
                str_contains($reason, 'cURL error 6') => ' The server could not resolve the host name (DNS).',
                str_contains($reason, 'cURL error 7') => ' The connection was refused or outbound HTTPS is blocked by the host.',
                default => '',
            };
            return 'Could not reach the endpoint: '.$reason.$hint;
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
            if (preg_match('#/models$#', $endpoint)) return "{$endpoint}/{$model}:generateContent";
            return $this->isBaseUrl($endpoint) ? "{$endpoint}/models/{$model}:generateContent" : $endpoint;
        }

        if ($endpoint === '') {
            return $provider['endpoint'] ?? ($api === 'anthropic' ? 'https://api.anthropic.com/v1/messages' : 'https://api.openai.com/v1/chat/completions');
        }

        // Only complete bare base URLs; any other path (custom gateways, Azure deployments) is used as entered.
        if (! $this->isBaseUrl($endpoint)) return $endpoint;

        $versioned = preg_match('#/v\d+(beta)?$#', $endpoint) === 1;
        if ($api === 'anthropic') {
            return $versioned ? "{$endpoint}/messages" : "{$endpoint}/v1/messages";
        }

        return $versioned ? "{$endpoint}/chat/completions" : "{$endpoint}/v1/chat/completions";
    }

    /** A URL with no path, or a path that ends in an API version such as /v1 or /openai/v1beta. */
    private function isBaseUrl(string $endpoint): bool
    {
        if (str_contains($endpoint, '?')) return false;
        $path = trim((string) parse_url($endpoint, PHP_URL_PATH), '/');

        return $path === '' || preg_match('#(^|/)v\d+(beta)?$#', $path) === 1;
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
        $http=Http::connectTimeout(15)->timeout(90);
        $auth=$provider['auth'] ?? 'bearer';
        if ($auth === 'api-key') $http=$http->withHeaders(['api-key'=>$c->apiKey()]);
        elseif ($auth !== 'none') $http=$http->withToken($c->apiKey());
        $r=$http->post($endpoint,$payload)->throw()->json();
        return $this->requireText(['text'=>(string)data_get($r,'choices.0.message.content',''),'input_tokens'=>(int)data_get($r,'usage.prompt_tokens',0),'output_tokens'=>(int)data_get($r,'usage.completion_tokens',0)], data_get($r,'choices.0.finish_reason'));
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
        $r=Http::connectTimeout(15)->timeout(90)->withHeaders(['x-api-key'=>$c->apiKey(),'anthropic-version'=>'2023-06-01'])
            ->post($endpoint,$payload)->throw()->json();
        // Responses can start with thinking blocks, so join every text block rather than reading content[0].
        $text=collect(data_get($r,'content',[]))->where('type','text')->pluck('text')->implode('');
        return $this->requireText(['text'=>$text,'input_tokens'=>(int)data_get($r,'usage.input_tokens',0),'output_tokens'=>(int)data_get($r,'usage.output_tokens',0)], data_get($r,'stop_reason'));
    }

    private function gemini(AiIntegration $c,array $provider,string $system,string $user,array $history=[]):array {
        $endpoint=$this->endpointFor($c,$provider);
        $r=Http::connectTimeout(15)->timeout(90)->withHeaders(['x-goog-api-key' => $c->apiKey()])->post($endpoint,[
            'system_instruction'=>['parts'=>[['text'=>$system]]],
            'contents'=>[
                ...array_map(fn($t)=>['role'=>$t['role']==='assistant'?'model':'user','parts'=>[['text'=>$t['content']]]],$history),
                ['role'=>'user','parts'=>[['text'=>$user]]],
            ],
        ])->throw()->json();
        if ($block = data_get($r,'promptFeedback.blockReason')) {
            throw new \UnexpectedValueException('Gemini blocked the request ('.$block.').');
        }
        // Replies can be split over several parts and may include thought parts; join the visible text.
        $text=collect(data_get($r,'candidates.0.content.parts',[]))
            ->reject(fn($part)=>!empty($part['thought']))
            ->pluck('text')->filter()->implode('');
        return $this->requireText([
            'text'=>$text,
            'input_tokens'=>(int)data_get($r,'usageMetadata.promptTokenCount',0),
            'output_tokens'=>(int)data_get($r,'usageMetadata.candidatesTokenCount',0),
        ], data_get($r,'candidates.0.finishReason'));
    }
}
