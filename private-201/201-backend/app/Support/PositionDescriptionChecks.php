<?php

namespace App\Support;

class PositionDescriptionChecks
{
    public static function defaultStakeholders(): array
    {
        return [
            'ieo' => false,
            'ief' => false,
            'iso' => false,
            'isf' => false,
            'ino' => false,
            'inf' => false,
            'isto' => false,
            'istf' => false,
            'egpo' => false,
            'egpf' => false,
            'eoao' => false,
            'eoaf' => false,
            'eoto' => false,
            'eotf' => false,
            'eots' => '',
        ];
    }

    public static function defaultWorkingCondition(): array
    {
        return [
            'owo' => false,
            'owf' => false,
            'fwo' => false,
            'fwf' => false,
            'oto' => false,
            'otf' => false,
            'ots' => '',
        ];
    }

    public static function parseStakeholders(?string $value): array
    {
        $defaults = self::defaultStakeholders();
        if ($value === null || trim($value) === '') {
            return $defaults;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $decoded)) {
                continue;
            }
            if ($key === 'eots') {
                $defaults[$key] = (string) $decoded[$key];
            } else {
                $defaults[$key] = self::toBool($decoded[$key]);
            }
        }

        return $defaults;
    }

    public static function parseWorkingCondition(?string $value): array
    {
        $defaults = self::defaultWorkingCondition();
        if ($value === null || trim($value) === '') {
            return $defaults;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $decoded)) {
                continue;
            }
            if ($key === 'ots') {
                $defaults[$key] = (string) $decoded[$key];
            } else {
                $defaults[$key] = self::toBool($decoded[$key]);
            }
        }

        return $defaults;
    }

    public static function salaryGradeNumber(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        if (preg_match('/\d+/', (string) $value, $matches)) {
            return $matches[0];
        }

        return trim((string) $value);
    }

    private static function toBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
