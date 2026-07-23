<?php

namespace App\Services\AI;

use App\DTO\AiRequestDTO;
use App\DTO\AiResponseDTO;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiGateway implements AiGatewayInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeout,
    ) {}

    public function extract(AiRequestDTO $request): AiResponseDTO
    {
        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->post(rtrim($this->baseUrl, '/') . '/chat/completions', [
                'model' => $this->model,
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $request->systemPrompt],
                    ['role' => 'user', 'content' => $request->userText],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('AI gateway request failed with status ' . $response->status());
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content)) {
            throw new RuntimeException('AI gateway response is missing message content');
        }

        $payload = json_decode($content, true);

        if (! is_array($payload)) {
            throw new RuntimeException('AI gateway returned invalid JSON payload');
        }

        return new AiResponseDTO($payload);
    }
}
