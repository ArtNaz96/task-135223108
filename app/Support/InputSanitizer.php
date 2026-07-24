<?php

namespace App\Support;

class InputSanitizer
{
    public static function sanitize(string $input): string
    {
        // 1. Strip HTML and PHP tags
        $clean = strip_tags($input);

        // 2. Remove control characters except \n (0x0A), \r (0x0D), and \t (0x09)
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);

        // 3. Process line by line to collapse horizontal spaces and trim edges
        $lines = explode("\n", $clean);
        $processedLines = array_map(function ($line) {
            // Remove \r if present at line end before processing
            $hasCr = str_ends_with($line, "\r");
            if ($hasCr) {
                $line = substr($line, 0, -1);
            }

            // Collapse multiple horizontal spaces/tabs
            $line = preg_replace('/[ \t]+/', ' ', $line);
            $line = trim($line, " \t");

            return $hasCr ? $line . "\r" : $line;
        }, $lines);

        $result = implode("\n", $processedLines);

        return trim($result, " \t\n\r");
    }
}