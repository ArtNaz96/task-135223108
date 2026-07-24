<?php

namespace App\Mail;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewFeedbackMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Feedback $feedback,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: trim(view('mail.new-feedback.subject', ['id' => $this->feedback->id])->render()),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.new-feedback.body',
            with: [
                'id' => $this->feedback->id,
                'comment' => $this->feedback->comment,
            ],
        );
    }
}
