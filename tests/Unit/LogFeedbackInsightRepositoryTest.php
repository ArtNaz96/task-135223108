<?php

namespace Tests\Unit;

use App\Models\FeedbackInsight;
use App\Repositories\LogFeedbackInsightRepository;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class LogFeedbackInsightRepositoryTest extends TestCase
{
    public function test_save_writes_to_feedback_insight_storage_channel(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);

        $insight = new FeedbackInsight(
            id: '20260724120000-123456',
            payload: ['intent' => ['intent_category' => 'other']],
        );

        $loggerMock->expects($this->once())
            ->method('info')
            ->with('AI feedback insight extracted', $insight->toArray());

        Log::shouldReceive('channel')
            ->once()
            ->with('feedback_insight_storage')
            ->andReturn($loggerMock);

        $repository = new LogFeedbackInsightRepository();
        $repository->save($insight);
    }
}
