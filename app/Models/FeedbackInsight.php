<?php

namespace App\Models;

class FeedbackInsight
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $id,
        public readonly array $payload,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'payload' => $this->payload,
        ];
    }
}
