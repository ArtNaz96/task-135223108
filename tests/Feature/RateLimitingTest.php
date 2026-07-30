<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use DatabaseMigrations;

    public function test_contact_form_rate_limiter_blocks_excessive_requests(): void
    {
        Mail::fake();
        Http::fake();
        $this->withHeader('Authorization', 'Bearer test-token');

        $data = [
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'john@example.com',
            'comment' => 'Test comment for rate limiting',
        ];

        // Первые 5 запросов проходят успешно
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/contact', $data);
            $response->assertStatus(201);
        }

        // 6-й запрос превышает лимит (5 зан/мин) и должен вернуть HTTP 429
        $response = $this->postJson('/api/v1/contact', $data);
        $response->assertStatus(429);
    }
}