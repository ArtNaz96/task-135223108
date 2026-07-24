<?php

namespace App\Services;

use App\Mail\NewFeedbackMail;
use App\Models\Feedback;
use Illuminate\Support\Facades\Mail;

class FeedbackNotifier
{
    public function notify(Feedback $feedback): void
    {
        Mail::to((string) config('services.site_owner.email'))->queue(new NewFeedbackMail($feedback));
        Mail::to($feedback->email)->queue(new NewFeedbackMail($feedback));
    }
}
