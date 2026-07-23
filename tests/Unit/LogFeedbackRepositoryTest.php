<?php

namespace Tests\Unit;

use App\DTO\Feedback;
use App\Repositories\LogFeedbackRepository;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class LogFeedbackRepositoryTest extends TestCase
{
    public function test_save_logs_feedback_data(): void
    {
        // 1. Создаем мок для самого PSR-логгера
        $loggerMock = $this->createMock(LoggerInterface::class);

        $feedback = new Feedback(
            name: 'John Doe',
            phone: '+1234567890',
            email: 'john@example.com',
            comment: 'AI DONE' . PHP_EOL . 'Hello world'
        );

        // 2. Ожидаем вызов info(...) с нужными аргументами
        $loggerMock->expects($this->once())
            ->method('info')
            ->with('New feedback received', $feedback->toArray());

        // 3. Указываем фасаду Log, что при запросе канала 'single' нужно вернуть наш мок
        Log::shouldReceive('channel')
            ->once()
            ->with('single')
            ->andReturn($loggerMock);

        $repository = new LogFeedbackRepository();
        $repository->save($feedback);
    }
}