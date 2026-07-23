<?php

namespace Tests\Unit;

use App\Models\Feedback;
use App\Repositories\FeedbackRepositoryInterface;
use App\Services\AiProcessingService;
use App\Services\FeedbackProcessingService;
use Tests\TestCase;

class FeedbackProcessingServiceTest extends TestCase
{
    public function test_process_saves_feedback_with_generated_id_and_unmodified_comment(): void
    {
        $inputData = [
            'name' => 'Jane Doe',
            'phone' => '+9876543210',
            'email' => 'jane@example.com',
            'comment' => 'Need help with project',
        ];

        $repositoryMock = $this->createMock(FeedbackRepositoryInterface::class);
        $repositoryMock->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Feedback $feedback) {
                return $feedback->name === 'Jane Doe'
                    && $feedback->phone === '+9876543210'
                    && $feedback->email === 'jane@example.com'
                    && $feedback->comment === 'Need help with project'
                    && preg_match('/^\d{14}-\d{6}$/', $feedback->id) === 1;
            }));

        $aiProcessingServiceMock = $this->createMock(AiProcessingService::class);
        $aiProcessingServiceMock->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf(Feedback::class));

        $service = new FeedbackProcessingService($repositoryMock, $aiProcessingServiceMock);
        $result = $service->process($inputData);

        $this->assertInstanceOf(Feedback::class, $result);
        $this->assertEquals('Jane Doe', $result->name);
        $this->assertEquals('Need help with project', $result->comment);
        $this->assertMatchesRegularExpression('/^\d{14}-\d{6}$/', $result->id);
    }

    public function test_process_saves_feedback_before_calling_ai_processing(): void
    {
        $inputData = [
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'john@example.com',
            'comment' => 'Order status',
        ];

        $callOrder = [];

        $repositoryMock = $this->createMock(FeedbackRepositoryInterface::class);
        $repositoryMock->method('save')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'repository.save';
        });

        $aiProcessingServiceMock = $this->createMock(AiProcessingService::class);
        $aiProcessingServiceMock->method('process')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'ai.process';
        });

        $service = new FeedbackProcessingService($repositoryMock, $aiProcessingServiceMock);
        $service->process($inputData);

        $this->assertSame(['repository.save', 'ai.process'], $callOrder);
    }
}
