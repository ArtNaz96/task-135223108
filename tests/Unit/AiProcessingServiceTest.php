<?php

namespace Tests\Unit;

use App\DTO\AiRequestDTO;
use App\DTO\AiResponseDTO;
use App\Models\Feedback;
use App\Models\FeedbackInsight;
use App\Repositories\FeedbackInsightRepositoryInterface;
use App\Services\AI\AiGatewayInterface;
use App\Services\AiProcessingService;
use RuntimeException;
use Tests\TestCase;

class AiProcessingServiceTest extends TestCase
{
    private function makeFeedback(): Feedback
    {
        return new Feedback(
            id: '20260724120000-123456',
            name: 'Jane Doe',
            phone: '+1234567890',
            email: 'jane@example.com',
            comment: 'Where is my order?',
        );
    }

    public function test_sends_comment_and_system_prompt_to_gateway(): void
    {
        $feedback = $this->makeFeedback();
        $systemPrompt = 'SYSTEM PROMPT TEXT';

        $gatewayMock = $this->createMock(AiGatewayInterface::class);
        $gatewayMock->expects($this->once())
            ->method('extract')
            ->with($this->callback(function (AiRequestDTO $request) use ($systemPrompt, $feedback) {
                return $request->systemPrompt === $systemPrompt
                    && $request->userText === $feedback->comment;
            }))
            ->willReturn(new AiResponseDTO(['intent' => ['intent_category' => 'other']]));

        $insightRepositoryMock = $this->createMock(FeedbackInsightRepositoryInterface::class);
        $insightRepositoryMock->expects($this->once())->method('save');

        $service = new AiProcessingService($gatewayMock, $insightRepositoryMock, $systemPrompt);
        $service->process($feedback);
    }

    public function test_saves_feedback_insight_on_successful_extraction(): void
    {
        $feedback = $this->makeFeedback();
        $payload = ['intent' => ['intent_category' => 'delay', 'urgency' => true]];

        $gatewayMock = $this->createMock(AiGatewayInterface::class);
        $gatewayMock->expects($this->once())->method('extract')->willReturn(new AiResponseDTO($payload));

        $insightRepositoryMock = $this->createMock(FeedbackInsightRepositoryInterface::class);
        $insightRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->callback(function (FeedbackInsight $insight) use ($feedback, $payload) {
                return $insight->id === $feedback->id
                    && $insight->payload === $payload;
            }));

        $service = new AiProcessingService($gatewayMock, $insightRepositoryMock, 'prompt');
        $service->process($feedback);
    }

    public function test_saves_payload_pruned_of_empty_values(): void
    {
        $feedback = $this->makeFeedback();
        $rawPayload = [
            'order' => ['order_id' => null, 'email' => null],
            'products' => [],
            'intent' => ['intent_category' => 'other', 'urgency' => false],
        ];

        $gatewayMock = $this->createMock(AiGatewayInterface::class);
        $gatewayMock->expects($this->once())->method('extract')->willReturn(new AiResponseDTO($rawPayload));

        $insightRepositoryMock = $this->createMock(FeedbackInsightRepositoryInterface::class);
        $insightRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->callback(function (FeedbackInsight $insight) {
                return $insight->payload === [
                    'intent' => ['intent_category' => 'other', 'urgency' => false],
                ];
            }));

        $service = new AiProcessingService($gatewayMock, $insightRepositoryMock, 'prompt');
        $service->process($feedback);
    }

    public function test_does_not_save_feedback_insight_on_gateway_failure(): void
    {
        $feedback = $this->makeFeedback();

        $gatewayMock = $this->createMock(AiGatewayInterface::class);
        $gatewayMock->expects($this->once())->method('extract')->willThrowException(new RuntimeException('gateway down'));

        $insightRepositoryMock = $this->createMock(FeedbackInsightRepositoryInterface::class);
        $insightRepositoryMock->expects($this->never())->method('save');

        $service = new AiProcessingService($gatewayMock, $insightRepositoryMock, 'prompt');
        $service->process($feedback);
    }

    public function test_does_not_propagate_gateway_exception(): void
    {
        $feedback = $this->makeFeedback();

        $gatewayMock = $this->createMock(AiGatewayInterface::class);
        $gatewayMock->expects($this->once())->method('extract')->willThrowException(new RuntimeException('gateway down'));

        $insightRepositoryMock = $this->createMock(FeedbackInsightRepositoryInterface::class);
        $insightRepositoryMock->expects($this->never())->method('save');

        $service = new AiProcessingService($gatewayMock, $insightRepositoryMock, 'prompt');

        // Не должно бросить исключение наружу.
        $service->process($feedback);

        $this->assertTrue(true);
    }
}
