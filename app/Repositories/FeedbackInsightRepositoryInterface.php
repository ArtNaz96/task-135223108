<?php

namespace App\Repositories;

use App\Models\FeedbackInsight;

interface FeedbackInsightRepositoryInterface
{
    public function save(FeedbackInsight $insight): void;
}
