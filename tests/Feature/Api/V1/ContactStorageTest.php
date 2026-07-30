<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class ContactStorageTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Authorization', 'Bearer test-token');
    }

    public function test_it_writes_valid_feedback_to_feedback_storage_channel(): void
    {
        Mail::fake();
        Http::fake();

        $payload = [
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'john@example.com',
            'comment' => 'Valid comment',
        ];

        $response = $this->postJson('/api/v1/contact', $payload);
        $response->assertStatus(201);

        $this->assertDatabaseHas('feedback', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_it_does_not_write_invalid_requests_to_feedback_storage_channel(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $response = $this->postJson('/api/v1/contact', $payload);
        $response->assertStatus(422);

        $this->assertDatabaseMissing('feedback', [
            'name' => 'John Doe',
        ]);
    }
}

