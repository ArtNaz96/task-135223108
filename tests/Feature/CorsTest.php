<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsTest extends TestCase
{
    public function test_api_returns_correct_cors_headers_on_options_preflight(): void
    {
        $response = $this->optionsJson('/api/v1/health', [], [
            'Origin' => 'http://example.com',
            'Access-Control-Request-Method' => 'GET',
        ]);

        $response->assertStatus(204)
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_api_contact_endpoint_returns_cors_headers_on_actual_request(): void
    {
        $response = $this->postJson('/api/v1/contact', [], [
            'Origin' => 'http://example.com',
        ]);

        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }
}