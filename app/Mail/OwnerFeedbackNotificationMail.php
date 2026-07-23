<?php

namespace App\Mail;

use App\DTO\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerFeedbackNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Feedback $feedback
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Новое обращение с сайта',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "<h2>Новое обращение</h2><p><b>Имя:</b> {$this->feedback->name}</p><p><b>Email:</b> {$this->feedback->email}</p><p><b>Телефон:</b> {$this->feedback->phone}</p><p><b>Сообщение:</b> {$this->feedback->comment}</p>",
        );
    }
}