<?php

namespace Tests\Unit;

use App\Models\Feedback;
use App\Repositories\LogFeedbackRepository;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class LogFeedbackRepositoryTest extends TestCase
{
    public function test_save_logs_feedback_data_to_feedback_storage_channel(): void
    {
        // 1. Создаем мок для самого PSR-логгера
        $loggerMock = $this->createMock(LoggerInterface::class);

        $feedback = new Feedback(
            id: '20260724120000-123456',
            name: 'John Doe',
            phone: '+1234567890',
            email: 'john@example.com',
            comment: 'Hello world'
        );

        // 2. Ожидаем вызов info(...) с нужными аргументами
        $loggerMock->expects($this->once())
            ->method('info')
            ->with('New feedback received', $feedback->toArray());

        // 3. Указываем фасаду Log, что при запросе канала 'feedback_storage' нужно вернуть наш мок
        Log::shouldReceive('channel')
            ->once()
            ->with('feedback_storage')
            ->andReturn($loggerMock);

        $repository = new LogFeedbackRepository();
        $repository->save($feedback);
    }
}
