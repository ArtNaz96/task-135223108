<?php

namespace Tests\Unit;

use App\DTO\AiRequestDTO;
use App\Services\AI\OpenAiGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiGatewayTest extends TestCase
{
    private function makeGateway(): OpenAiGateway
    {
        return new OpenAiGateway(
            baseUrl: 'http://127.0.0.1:8101/openai/v1',
            apiKey: 'test-key',
            model: 'james',
            timeout: 5,
        );
    }

    public function test_calls_chat_completions_endpoint_with_expected_body_and_headers(): void
    {
        Http::fake([
            'http://127.0.0.1:8101/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"intent":{"intent_category":"other"}}']],
                ],
            ], 200),
        ]);

        $response = $this->makeGateway()->extract(new AiRequestDTO('SYSTEM', 'USER TEXT'));

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:8101/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'james'
                && $request['temperature'] === 0
                && $request['response_format'] === ['type' => 'json_object']
                && $request['messages'][0] === ['role' => 'system', 'content' => 'SYSTEM']
                && $request['messages'][1] === ['role' => 'user', 'content' => 'USER TEXT'];
        });

        $this->assertSame(['intent' => ['intent_category' => 'other']], $response->payload);
    }

    public function test_throws_on_invalid_json_response(): void
    {
        Http::fake([
            'http://127.0.0.1:8101/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'not a json']],
                ],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);

        $this->makeGateway()->extract(new AiRequestDTO('SYSTEM', 'USER TEXT'));
    }

    public function test_throws_when_request_fails(): void
    {
        Http::fake([
            'http://127.0.0.1:8101/openai/v1/chat/completions' => Http::response([], 500),
        ]);

        $this->expectException(RuntimeException::class);

        $this->makeGateway()->extract(new AiRequestDTO('SYSTEM', 'USER TEXT'));
    }
}
