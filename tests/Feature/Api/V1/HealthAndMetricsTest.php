<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class HealthAndMetricsTest extends TestCase
{
    public function test_it_returns_ok_health_status(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_it_returns_not_implemented_for_metrics(): void
    {
        $response = $this->getJson('/api/v1/metrics');

        $response->assertStatus(501);
    }
}