<?php

namespace App\Mail;

use App\DTO\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserAutoReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Feedback $feedback
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm | Подтверждение',
        );
    }

    public function content(): Content
    {
        $body = "New comment from {$this->feedback->email}\n" .
            "Новый комментарий от {$this->feedback->email}\n\n" .
            $this->feedback->comment;

        return new Content(
            htmlString: nl2br(e($body)),
        );
    }
}