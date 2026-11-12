<?php

namespace App\Services\Amis\Data;

use App\Services\Amis\AreaUnitConverter;
use App\Services\SourceApi\ParsesSourceValues;
use App\Support\LaoAdministrativeCode;

/**
 * A land plot (items[]) of an AMIS contract.
 *
 * Quirks handled here: dristric_id is a string; province_id is sometimes 0, in which case the
 * province is taken from the district code (district 104 is in province 1), then from the
 * partner address; unit is free text and may not convert to hectares.
 */
final readonly class AmisContractItemData
{
    use ParsesSourceValues;

    public function __construct(
        public int $id,
        public ?string $landUseDetail,
        public ?int $provinceId,
        public ?int $districtId,
        public ?string $districtName,
        public ?string $village,
        public ?float $area,
        public ?string $unit,
        public ?float $areaHa,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromApi(array $raw, ?int $partnerProvinceId, AreaUnitConverter $units): ?self
    {
        $id = self::positiveIntOrNull($raw['id'] ?? null);

        if ($id === null) {
            return null;
        }

        $districtId = self::positiveIntOrNull($raw['dristric_id'] ?? null);
        $area = self::floatOrNull($raw['area'] ?? null);
        $unit = self::stringOrNull($raw['unit'] ?? null);

        return new self(
            id: $id,
            landUseDetail: self::stringOrNull($raw['rent_detell'] ?? null),
            provinceId: self::positiveIntOrNull($raw['province_id'] ?? null)
                ?? LaoAdministrativeCode::provinceOfDistrict($districtId)
                ?? $partnerProvinceId,
            districtId: $districtId,
            districtName: self::stringOrNull($raw['district_name'] ?? null),
            village: self::stringOrNull($raw['village'] ?? null),
            area: $area,
            unit: $unit,
            areaHa: $area !== null && $area > 0 ? $units->toHectares($area, $unit) : null,
        );
    }

    /**
     * Whether the item has an area that could not be converted because its unit is unknown.
     */
    public function hasUnknownUnit(): bool
    {
        return $this->area !== null && $this->area > 0 && $this->areaHa === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(int $contractId): array
    {
        return [
            'id' => $this->id,
            'amis_contract_id' => $contractId,
            'land_use_detail' => $this->landUseDetail,
            'province_id' => $this->provinceId,
            'district_id' => $this->districtId,
            'district_name' => $this->districtName,
            'village' => $this->village,
            'area' => $this->area,
            'unit' => $this->unit,
            'area_ha' => $this->areaHa,
        ];
    }

    /**
     * @return array{?string, ?int, ?int, ?string, ?float}
     */
    public function keyFields(): array
    {
        return [$this->landUseDetail, $this->provinceId, $this->districtId, $this->village, $this->areaHa];
    }
}
