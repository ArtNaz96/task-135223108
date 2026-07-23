<?php

namespace App\Repositories;

use App\DTO\Feedback;

interface FeedbackRepositoryInterface
{
    public function save(Feedback $feedback): void;
}