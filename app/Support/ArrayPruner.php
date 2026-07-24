<?php

namespace App\Support;

class ArrayPruner
{
    /**
     * Рекурсивно убирает из массива пустые значения (null, '', []),
     * включая ключи, у которых после очистки вложенных массивов ничего не осталось.
     *
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    public static function pruneEmpty(array $data): array
    {
        $isList = array_is_list($data);
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::pruneEmpty($value);

                if ($value === []) {
                    continue;
                }
            } elseif ($value === null || $value === '') {
                continue;
            }

            $result[$key] = $value;
        }

        return $isList ? array_values($result) : $result;
    }
}
