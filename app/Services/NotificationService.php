<?php

namespace App\Services;

use App\DTO\Feedback;
use App\Jobs\SendUserAutoReplyJob;

class NotificationService
{
    public function sendFeedbackNotifications(Feedback $feedback): void
    {
        SendUserAutoReplyJob::dispatch($feedback);
    }
}