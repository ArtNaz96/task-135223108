<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ContactValidationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Тестовый токен из phpunit.xml (API_ACCESS_TOKEN=test-token) — не реальный секрет из .env.
        $this->withHeader('Authorization', 'Bearer test-token');
    }

    public function test_it_accepts_valid_payload(): void
    {
        Mail::fake();
        Http::fake();

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
}
