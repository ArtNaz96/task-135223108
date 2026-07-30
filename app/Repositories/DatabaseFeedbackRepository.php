<?php

namespace App\Repositories;

use App\Models\Feedback;
use App\Models\FeedbackRecord;

class DatabaseFeedbackRepository implements FeedbackRepositoryInterface
{
    public function save(Feedback $feedback): void
    {
        FeedbackRecord::create([
            'id' => $feedback->id,
            'name' => $feedback->name,
            'phone' => $feedback->phone,
            'email' => $feedback->email,
            'comment' => $feedback->comment,
        ]);
    }
}