<?php

namespace App\Repositories;

use App\Models\FeedbackInsight;
use App\Models\Insight;

class DatabaseFeedbackInsightRepository implements FeedbackInsightRepositoryInterface
{
    public function save(FeedbackInsight $insight): void
    {
        Insight::create([
            'id' => $insight->id,
            'payload' => $insight->payload,
        ]);
    }
}