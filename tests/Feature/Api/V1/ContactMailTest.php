<?php

namespace Tests\Feature\Api\V1;

use App\Mail\NewFeedbackMail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactMailTest extends TestCase
{
    use DatabaseMigrations;

    private function validPayload(): array
    {
        return [
            'name' => 'Jane Doe',
            'phone' => '+1234567890',
            'email' => 'jane@example.com',
            'comment' => 'Where is my order?',
        ];
    }

    public function test_successful_request_sends_two_separate_mails_to_owner_and_user(): void
    {
        Mail::fake();
        Http::fake();
        config(['services.site_owner.email' => 'owner@example.com']);

        $payload = $this->validPayload();
        $response = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/v1/contact', $payload);

        $response->assertStatus(201);

        Mail::assertQueuedCount(2);
        Mail::assertQueued(NewFeedbackMail::class, function (NewFeedbackMail $mail) use ($payload) {
            return $mail->hasTo('owner@example.com') && $mail->cc === [];
        });
        Mail::assertQueued(NewFeedbackMail::class, function (NewFeedbackMail $mail) use ($payload) {
            return $mail->hasTo($payload['email']) && $mail->cc === [];
        });
    }

    public function test_validation_failure_does_not_send_mail(): void
    {
        Mail::fake();

        $response = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/v1/contact', [
                'name' => 'Jane Doe',
                // phone/email/comment отсутствуют
            ]);

        $response->assertStatus(422);
        Mail::assertNothingQueued();
    }
}

