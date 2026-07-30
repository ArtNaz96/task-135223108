<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ContactTokenAuthTest extends TestCase
{
    use DatabaseMigrations;

    private function validPayload(): array
    {
        return [
            'name' => 'Jane Doe',
            'phone' => '+1234567890',
            'email' => 'jane@example.com',
            'comment' => 'Where is my order?',
        ];
    }

    public function test_rejects_request_without_token(): void
    {
        Mail::fake();
        Http::fake();

        $response = $this->postJson('/api/v1/contact', $this->validPayload());

        $response->assertStatus(401);
        Mail::assertNothingQueued();
    }

    public function test_rejects_request_with_wrong_token(): void
    {
        Mail::fake();
        Http::fake();

        $response = $this->withHeader('Authorization', 'Bearer wrong-token')
            ->postJson('/api/v1/contact', $this->validPayload());

        $response->assertStatus(401);
        Mail::assertNothingQueued();
    }

    public function test_accepts_request_with_correct_token(): void
    {
        Mail::fake();
        Http::fake();

        $response = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/v1/contact', $this->validPayload());

        $response->assertStatus(201);
    }
}
