<?php

namespace Tests\Feature\Api\V1;

use App\Repositories\FeedbackRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class ContactTest extends TestCase
{
    public function test_it_accepts_valid_payload(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'john@example.com',
            'comment' => 'Valid feedback comment',
        ];

        $response = $this->postJson('/api/v1/contact', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'phone', 'email', 'comment'],
            ]);
    }

    public function test_it_rejects_missing_required_field(): void
    {
        Mail::fake();

        $fields = ['name', 'phone', 'email', 'comment'];

        foreach ($fields as $field) {
            $payload = [
                'name' => 'John Doe',
                'phone' => '+1234567890',
                'email' => 'john@example.com',
                'comment' => 'Valid comment',
            ];
            unset($payload[$field]);

            $response = $this->postJson('/api/v1/contact', $payload);
            $response->assertStatus(422)
                ->assertJsonValidationErrors([$field]);
        }
    }

    public function test_it_rejects_invalid_email_format(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'not-an-email',
            'comment' => 'Valid comment',
        ];

        $response = $this->postJson('/api/v1/contact', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_rejects_comment_longer_than_2000_chars(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'john@example.com',
            'comment' => str_repeat('a', 2001),
        ];

        $response = $this->postJson('/api/v1/contact', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_it_returns_500_without_leaking_internals_on_unexpected_error(): void
    {
        Mail::fake();

        $mockRepo = $this->createMock(FeedbackRepositoryInterface::class);
        $mockRepo->method('save')
            ->willThrowException(new \RuntimeException('Database failure'));

        $this->app->instance(FeedbackRepositoryInterface::class, $mockRepo);

        $payload = [
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'john@example.com',
            'comment' => 'Test comment',
        ];

        $response = $this->postJson('/api/v1/contact', $payload);

        $response->assertStatus(500)
            ->assertJsonStructure(['message']);
    }

    public function test_it_writes_valid_feedback_to_feedback_storage_channel(): void
    {
        Mail::fake();

        $feedbackLoggerMock = $this->createMock(LoggerInterface::class);
        $feedbackLoggerMock->expects($this->once())
            ->method('info');

        $defaultLoggerMock = $this->createMock(LoggerInterface::class);

        Log::shouldReceive('channel')
            ->with('feedback_storage')
            ->andReturn($feedbackLoggerMock);

        Log::shouldReceive('channel')
            ->with(null)
            ->andReturn($defaultLoggerMock);

        Log::shouldReceive('error')
            ->byDefault();

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