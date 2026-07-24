<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class ContactStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Тестовый токен из phpunit.xml (API_ACCESS_TOKEN=test-token) — не реальный секрет из .env.
        $this->withHeader('Authorization', 'Bearer test-token');
    }

    public function test_it_writes_valid_feedback_to_feedback_storage_channel(): void
    {
        Mail::fake();
        Http::fake();

        $feedbackLoggerMock = $this->createMock(LoggerInterface::class);
        $feedbackLoggerMock->expects($this->once())
            ->method('info');

        Log::shouldReceive('channel')
            ->with('feedback_storage')
            ->andReturn($feedbackLoggerMock);

        Log::shouldReceive('channel')
            ->withAnyArgs()
            ->andReturnUsing(fn () => $this->createMock(LoggerInterface::class));

        Log::shouldReceive('error')->byDefault();

        $payload = [
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'john@example.com',
            'comment' => 'Valid comment',
        ];

        $response = $this->postJson('/api/v1/contact', $payload);
        $response->assertStatus(201);
    }

    public function test_it_does_not_write_invalid_requests_to_feedback_storage_channel(): void
    {
        Mail::fake();

        $feedbackLoggerMock = $this->createMock(LoggerInterface::class);
        $feedbackLoggerMock->expects($this->never())
            ->method('info');

        Log::shouldReceive('channel')
            ->with('feedback_storage')
            ->andReturn($feedbackLoggerMock);

        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $response = $this->postJson('/api/v1/contact', $payload);
        $response->assertStatus(422);
    }
}
