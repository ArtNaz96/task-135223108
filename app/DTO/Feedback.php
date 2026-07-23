<?php

namespace App\DTO;

class Feedback
{
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $email,
        public readonly string $comment,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'comment' => $this->comment,
        ];
    }
}