<?php

namespace App\Support;

/**
 * Lao national administrative codes, shared by LCIS and AMIS: provinces 1–18, districts
 * province × 100 + n (district 104 is in province 1, 1601 in province 16).
 */
final class LaoAdministrativeCode
{
    private const MAX_PROVINCE_ID = 18;

    public static function provinceOfDistrict(?int $districtId): ?int
    {
        if ($districtId === null) {
            return null;
        }

        $provinceId = intdiv($districtId, 100);

        return $provinceId >= 1 && $provinceId <= self::MAX_PROVINCE_ID ? $provinceId : null;
    }
}
