<?php

namespace App\Services;

class AiAnalysisService
{
    public function analyze(string $text): string
    {
        return "AI DONE\n" . $text;
    }
}