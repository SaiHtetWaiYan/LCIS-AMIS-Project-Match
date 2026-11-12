<?php

namespace App\Services\Amis;

/**
 * Converts AMIS item areas to hectares using the unit factors in config/matching.php.
 */
class AreaUnitConverter
{
    /**
     * Returns null when the unit is not known; callers treat that as "no area data".
     */
    public function toHectares(float $area, ?string $unit): ?float
    {
        $factor = $this->factors()[self::normalize($unit ?? '')] ?? null;

        return $factor === null ? null : $area * $factor;
    }

    public static function normalize(string $unit): string
    {
        $unit = mb_strtolower($unit);
        $unit = str_replace('²', '2', $unit);

        return preg_replace('/[\s\x{200B}-\x{200D}\x{FEFF}.]+/u', '', $unit) ?? $unit;
    }

    /**
     * @return array<string, float>
     */
    private function factors(): array
    {
        $factors = [];

        foreach (config('matching.area_units') as $unit => $factor) {
            $factors[self::normalize($unit)] = (float) $factor;
        }

        return $factors;
    }
}
