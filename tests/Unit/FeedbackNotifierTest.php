<?php

namespace Tests\Unit;

use App\Mail\NewFeedbackMail;
use App\Models\Feedback;
use App\Services\FeedbackNotifier;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FeedbackNotifierTest extends TestCase
{
    private function makeFeedback(): Feedback
    {
        return new Feedback(
            id: '20260724120000-123456',
            name: 'Jane Doe',
            phone: '+1234567890',
            email: 'jane@example.com',
            comment: 'Hello',
        );
    }

    public function test_sends_mail_to_owner_with_user_cc(): void
    {
        Mail::fake();
        config(['services.site_owner.email' => 'owner@example.com']);

        $feedback = $this->makeFeedback();

        (new FeedbackNotifier())->notify($feedback);

        Mail::assertQueued(NewFeedbackMail::class, function (NewFeedbackMail $mail) use ($feedback) {
            return $mail->hasTo('owner@example.com')
                && $mail->hasCc($feedback->email)
                && $mail->feedback->id === $feedback->id;
        });
    }

    public function test_sends_exactly_one_mail(): void
    {
        Mail::fake();
        config(['services.site_owner.email' => 'owner@example.com']);

        (new FeedbackNotifier())->notify($this->makeFeedback());

        Mail::assertQueuedCount(1);
    }
}
