<?php

namespace App\Services\SourceApi;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Lenient readers for source payload values, which mix types ("104" vs 104) and use
 * placeholders such as "", "0" or "0000-00-00" for missing data.
 */
trait ParsesSourceValues
{
    protected static function positiveIntOrNull(mixed $value): ?int
    {
        if (is_int($value) || (is_string($value) && ctype_digit(trim($value)))) {
            $int = (int) $value;

            return $int > 0 ? $int : null;
        }

        if (is_float($value) && $value > 0 && floor($value) === $value) {
            return (int) $value;
        }

        return null;
    }

    protected static function floatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    protected static function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    protected static function dateOrNull(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || str_starts_with($value, '0000')) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : null;
    }

    protected static function timestampOrNull(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '' || str_starts_with($value, '0000')) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    protected static function payloadHash(array $raw): string
    {
        return hash('sha256', json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  array<mixed>  $fields
     */
    protected static function fieldsHash(array $fields): string
    {
        return hash('sha256', json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
