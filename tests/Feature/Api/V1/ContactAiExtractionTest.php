<?php

namespace Tests\Feature\Api\V1;

use App\DTO\AiResponseDTO;
use App\Repositories\FeedbackInsightRepositoryInterface;
use App\Services\AI\AiGatewayInterface;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ContactAiExtractionTest extends TestCase
{
    private function validPayload(): array
    {
        return [
            'name' => 'Jane Doe',
            'phone' => '+1234567890',
            'email' => 'jane@example.com',
            'comment' => 'Where is my order #123?',
        ];
    }

    public function test_successful_ai_extraction_saves_feedback_insight(): void
    {
        Mail::fake();

        $payload = ['intent' => ['intent_category' => 'delay']];

        $gatewayMock = Mockery::mock(AiGatewayInterface::class);
        $gatewayMock->shouldReceive('extract')
            ->once()
            ->andReturn(new AiResponseDTO($payload));
        $this->app->instance(AiGatewayInterface::class, $gatewayMock);

        $insightRepositoryMock = Mockery::mock(FeedbackInsightRepositoryInterface::class);
        $insightRepositoryMock->shouldReceive('save')
            ->once()
            ->withArgs(fn ($insight) => $insight->payload === $payload);
        $this->app->instance(FeedbackInsightRepositoryInterface::class, $insightRepositoryMock);

        $response = $this->postJson('/api/v1/contact', $this->validPayload());

        $response->assertStatus(201);
    }

    public function test_ai_gateway_failure_does_not_break_the_request(): void
    {
        Mail::fake();

        $gatewayMock = Mockery::mock(AiGatewayInterface::class);
        $gatewayMock->shouldReceive('extract')
            ->once()
            ->andThrow(new RuntimeException('gateway down'));
        $this->app->instance(AiGatewayInterface::class, $gatewayMock);

        $insightRepositoryMock = Mockery::mock(FeedbackInsightRepositoryInterface::class);
        $insightRepositoryMock->shouldNotReceive('save');
        $this->app->instance(FeedbackInsightRepositoryInterface::class, $insightRepositoryMock);

        $response = $this->postJson('/api/v1/contact', $this->validPayload());

        // Провал AI не должен ломать основной сценарий (graceful fallback).
        $response->assertStatus(201);
    }
}
