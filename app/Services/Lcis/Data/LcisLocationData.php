<?php

namespace App\Services\Lcis\Data;

use App\Services\SourceApi\ParsesSourceValues;
use App\Support\LaoAdministrativeCode;

/**
 * A land location of an LCIS project, from locations[] ({ dam_name, id_prov, prov_name, id_dist, dist_name,
 * id_vill, vill_name, ... }) or, as a fallback, the responsible province/district.
 */
final readonly class LcisLocationData
{
    use ParsesSourceValues;

    public function __construct(
        public ?int $provinceId,
        public ?string $provinceName,
        public ?int $districtId,
        public ?string $districtName,
        public ?int $villageId,
        public ?string $villageName,
        public bool $isFallback,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromApi(array $raw): self
    {
        $districtId = self::positiveIntOrNull($raw['id_dist'] ?? null);

        return new self(
            self::positiveIntOrNull($raw['id_prov'] ?? null) ?? LaoAdministrativeCode::provinceOfDistrict($districtId),
            self::stringOrNull($raw['prov_name'] ?? null),
            $districtId,
            self::stringOrNull($raw['dist_name'] ?? null),
            self::positiveIntOrNull($raw['id_vill'] ?? null),
            self::stringOrNull($raw['vill_name'] ?? null),
            false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(int $projectId): array
    {
        return [
            'lcis_project_id' => $projectId,
            'province_id' => $this->provinceId,
            'province_name' => $this->provinceName,
            'district_id' => $this->districtId,
            'district_name' => $this->districtName,
            'village_id' => $this->villageId,
            'village_name' => $this->villageName,
            'is_fallback' => $this->isFallback,
        ];
    }

    /**
     * @return array{?int, ?int, ?string}
     */
    public function keyFields(): array
    {
        return [$this->provinceId, $this->districtId, $this->villageName];
    }
}
