<?php

namespace App\Repositories;

use App\Models\Feedback;

interface FeedbackRepositoryInterface
{
    public function save(Feedback $feedback): void;
}