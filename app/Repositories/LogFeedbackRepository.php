<?php

namespace App\Repositories;

use App\DTO\Feedback;
use Illuminate\Support\Facades\Log;

class LogFeedbackRepository implements FeedbackRepositoryInterface
{
    public function save(Feedback $feedback): void
    {
        Log::channel('single')->info('New feedback received', $feedback->toArray());
    }
}