<?php

namespace App\DTO;

final class AiRequestDTO
{
    public function __construct(
        public readonly string $systemPrompt,
        public readonly string $userText,
    ) {}
}
