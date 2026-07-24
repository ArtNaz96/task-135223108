<?php

namespace App\Services;

use App\DTO\AiRequestDTO;
use App\Models\Feedback;
use App\Models\FeedbackInsight;
use App\Repositories\FeedbackInsightRepositoryInterface;
use App\Services\AI\AiGatewayInterface;
use App\Support\ArrayPruner;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiProcessingService
{
    public function __construct(
        private readonly AiGatewayInterface $aiGateway,
        private readonly FeedbackInsightRepositoryInterface $feedbackInsightRepository,
        private readonly string $systemPrompt,
    ) {}

    public function process(Feedback $feedback): void
    {
        try {
            $response = $this->aiGateway->extract(
                new AiRequestDTO($this->systemPrompt, $feedback->comment)
            );

            $this->feedbackInsightRepository->save(
                new FeedbackInsight($feedback->id, ArrayPruner::pruneEmpty($response->payload))
            );
        } catch (Throwable $e) {
            Log::error('AI feedback extraction failed', [
                'feedback_id' => $feedback->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
