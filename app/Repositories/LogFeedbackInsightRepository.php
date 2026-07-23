<?php

namespace App\Repositories;

use App\Models\FeedbackInsight;
use Illuminate\Support\Facades\Log;

class LogFeedbackInsightRepository implements FeedbackInsightRepositoryInterface
{
    public function save(FeedbackInsight $insight): void
    {
        Log::channel('feedback_insight_storage')->info('AI feedback insight extracted', $insight->toArray());
    }
}
