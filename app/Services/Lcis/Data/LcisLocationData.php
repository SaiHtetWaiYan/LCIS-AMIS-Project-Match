<?php

namespace App\Services\Lcis\Data;

use App\Services\SourceApi\ParsesSourceValues;

/**
 * A land location of an LCIS project, from locations[] or (as a fallback) the responsible province/district.
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
        return new self(
            self::positiveIntOrNull($raw['province_id'] ?? null),
            self::stringOrNull($raw['province_name'] ?? null),
            self::positiveIntOrNull($raw['district_id'] ?? null),
            self::stringOrNull($raw['district_name'] ?? null),
            self::positiveIntOrNull($raw['village_id'] ?? null),
            self::stringOrNull($raw['village_name'] ?? null),
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
