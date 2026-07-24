<?php

namespace Tests\Feature\Api\V1;

use App\Repositories\FeedbackRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ContactErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Тестовый токен из phpunit.xml (API_ACCESS_TOKEN=test-token) — не реальный секрет из .env.
        $this->withHeader('Authorization', 'Bearer test-token');
    }

    public function test_it_returns_500_without_leaking_internals_on_unexpected_error(): void
    {
        Mail::fake();

        $mockRepo = $this->createMock(FeedbackRepositoryInterface::class);
        $mockRepo->expects($this->once())
            ->method('save')
            ->willThrowException(new RuntimeException('Database failure'));

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
}
