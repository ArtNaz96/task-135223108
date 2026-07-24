<?php

namespace App\Services\AI;

use App\DTO\AiRequestDTO;
use App\DTO\AiResponseDTO;

interface AiGatewayInterface
{
    /**
     * @throws \Throwable если провайдер недоступен, вернул ошибку, невалидный JSON или истёк таймаут.
     */
    public function extract(AiRequestDTO $request): AiResponseDTO;
}
