<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector;

class Utils
{
    public static function filterEnabled(array $items): array
    {
        return (gettype(reset($items)) === 'boolean') ?
            array_keys($items, true, true)
            : $items;
    }

    public static function normalizeBooleanMap(array $items, bool $defaultValue = true): array
    {
        if (!$items || gettype(reset($items)) === 'boolean') {
            return $items;
        }

        return array_fill_keys($items, $defaultValue);
    }
}
