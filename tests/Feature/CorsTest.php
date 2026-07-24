<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $origins = 'http://127.0.0.1:8080,http://192.168.1.100:3000,http://localhost:3000';
        Config::set('cors.allowed_origins', array_map('trim', explode(',', $origins)));
    }

    public function test_allows_request_from_allowed_origin(): void
    {
        $allowedOrigin = 'http://192.168.1.100:3000';

        $response = $this->optionsJson('/api/v1/health', [], [
            'Origin' => $allowedOrigin,
            'Access-Control-Request-Method' => 'GET',
        ]);

        $response->assertStatus(204)
            ->assertHeader('Access-Control-Allow-Origin', $allowedOrigin);
    }

    public function test_blocks_request_from_disallowed_origin(): void
    {
        $disallowedOrigin = 'http://evil-site.com:8080';

        $response = $this->optionsJson('/api/v1/health', [], [
            'Origin' => $disallowedOrigin,
            'Access-Control-Request-Method' => 'GET',
        ]);

        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_blocks_request_when_port_does_not_match(): void
    {
        // 127.0.0.1:8080 разрешен, но 127.0.0.1:9090 должен быть заблокирован
        $disallowedOrigin = 'http://127.0.0.1:9090';

        $response = $this->optionsJson('/api/v1/health', [], [
            'Origin' => $disallowedOrigin,
            'Access-Control-Request-Method' => 'GET',
        ]);

        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}