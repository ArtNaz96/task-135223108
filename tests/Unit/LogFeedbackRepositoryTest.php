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
        Log::fake();

        $repository = new LogFeedbackRepository();
        $feedback = new Feedback(
            name: 'John Doe',
            phone: '+1234567890',
            email: 'john@example.com',
            comment: 'AI DONE' . PHP_EOL . 'Hello world'
        );

        $repository->save($feedback);

        Log::channel('single')->assertLogged('info', function (string $message, array $context) use ($feedback) {
            return $message === 'New feedback received'
                && $context === $feedback->toArray();
        });
    }
}