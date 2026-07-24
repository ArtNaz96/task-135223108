<?php

namespace App\Repositories;

use App\Models\Feedback;
use Illuminate\Support\Facades\Log;

class LogFeedbackRepository implements FeedbackRepositoryInterface
{
    public function save(Feedback $feedback): void
    {
        Log::channel('feedback_storage')->info('New feedback received', $feedback->toArray());
    }
}