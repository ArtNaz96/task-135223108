<?php

namespace Tests\Unit;

use App\DTO\Feedback;
use App\Repositories\FeedbackRepositoryInterface;
use App\Services\AiAnalysisService;
use App\Services\FeedbackProcessingService;
use Tests\TestCase;

class FeedbackProcessingServiceTest extends TestCase
{
    public function test_process_analyzes_comment_and_saves_feedback(): void
    {
        $inputData = [
            'name' => 'Jane Doe',
            'phone' => '+9876543210',
            'email' => 'jane@example.com',
            'comment' => 'Need help with project',
        ];

        $aiServiceMock = $this->createMock(AiAnalysisService::class);
        $aiServiceMock->expects($this->once())
            ->method('analyze')
            ->with('Need help with project')
            ->willReturn("AI DONE\nNeed help with project");

        $repositoryMock = $this->createMock(FeedbackRepositoryInterface::class);
        $repositoryMock->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Feedback $feedback) {
                return $feedback->name === 'Jane Doe'
                    && $feedback->phone === '+9876543210'
                    && $feedback->email === 'jane@example.com'
                    && $feedback->comment === "AI DONE\nNeed help with project";
            }));

        $service = new FeedbackProcessingService($repositoryMock, $aiServiceMock);
        $result = $service->process($inputData);

        $this->assertInstanceOf(Feedback::class, $result);
        $this->assertEquals('Jane Doe', $result->name);
        $this->assertEquals("AI DONE\nNeed help with project", $result->comment);
    }
}
