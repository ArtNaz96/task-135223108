<?php

namespace Tests\Unit;

use App\DTO\Feedback;
use App\Repositories\LogFeedbackRepository;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogFeedbackRepositoryTest extends TestCase
{
    public function test_save_logs_feedback_data(): void
    {
        // Создаем шпион для фасада Log
        Log::spy();

        $repository = new LogFeedbackRepository();
        $feedback = new Feedback(
            name: 'John Doe',
            phone: '+1234567890',
            email: 'john@example.com',
            comment: 'AI DONE' . PHP_EOL . 'Hello world'
        );

        $repository->save($feedback);

        // Проверяем, что Log::channel('single')->info(...) был вызван с нужными аргументами
        Log::channel('single')->shouldHaveReceived('info')
            ->once()
            ->with('New feedback received', $feedback->toArray());
    }
}