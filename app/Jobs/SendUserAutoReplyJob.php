<?php

namespace App\Jobs;

use App\DTO\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendUserAutoReplyJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly Feedback $feedback
    ) {}

    public function handle(): void
    {
        // Логика отправки автоматического ответа пользователю
    }
}