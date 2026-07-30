<?php

namespace Tests\Feature\Api\V1;

use App\DTO\AiResponseDTO;
use App\Services\AI\AiGatewayInterface;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ContactDatabaseTest extends TestCase
{
    use DatabaseMigrations;

    public function test_contact_form_submission_stores_feedback_and_insights_in_database(): void
    {
        $mockAiGateway = $this->createMock(AiGatewayInterface::class);
        $mockAiGateway->method('extract')
            ->willReturn(new AiResponseDTO([
                'summary' => 'Запрос стоимости API',
                'sentiment' => 'positive',
                'category' => 'lead',
            ]));

        $this->app->instance(AiGatewayInterface::class, $mockAiGateway);

        $payload = [
            'name' => 'Алексей Смирнов',
            'phone' => '+79991234567',
            'email' => 'alexey@example.com',
            'comment' => 'Здравствуйте! Интересует стоимость и сроки разработки REST API.',
        ];

        $response = $this->postJson('/api/v1/contact', $payload, [
            'Authorization' => 'Bearer test-token',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Feedback successfully received');

        $feedbackId = $response->json('data.id');

        $this->assertDatabaseHas('feedback', [
            'id' => $feedbackId,
            'name' => 'Алексей Смирнов',
            'phone' => '+79991234567',
            'email' => 'alexey@example.com',
            'comment' => 'Здравствуйте! Интересует стоимость и сроки разработки REST API.',
        ]);

        $this->assertDatabaseHas('insights', [
            'id' => $feedbackId,
        ]);
    }
}