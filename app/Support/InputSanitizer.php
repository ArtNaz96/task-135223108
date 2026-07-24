<?php

namespace App\Support;

class InputSanitizer
{
    /**
     * Санитизация "параноидальная", но без искажения смысла:
     * - убирает управляющие/невидимые символы (кроме \n \r \t — иначе ломаем многострочный текст),
     * - убирает HTML/скрипт-теги целиком (а не экранирует — чтобы в письме/логе не было "&lt;script&gt;"),
     * - схлопывает только горизонтальные пробелы, переносы строк не трогает.
     */
    public static function sanitize(string $value): string
    {
        // null-байт и управляющие символы, кроме \t(\x09) \n(\x0A) \r(\x0D)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        // HTML/script-теги — вырезаем целиком (XSS), не оставляя мусора из сущностей
        $value = strip_tags($value);

        // горизонтальные пробелы/табы схлопываем, переносы строк оставляем как есть
        $value = preg_replace('/[ \t]+/u', ' ', $value);

        // пробелы по краям каждой строки, не трогая сами переносы
        $value = implode("\n", array_map('trim', explode("\n", $value)));

        return trim($value);
    }
}
