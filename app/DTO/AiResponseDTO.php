<?php

namespace App\DTO;

final class AiResponseDTO
{
    /**
     * @param array<string, mixed> $payload Распарсенный JSON, возвращённый AI-провайдером.
     */
    public function __construct(
        public readonly array $payload,
    ) {}
}
