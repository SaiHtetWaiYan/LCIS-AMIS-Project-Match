<?php

namespace App\Services\Lcis\Data;

use App\Exceptions\SourceApi\InvalidSourceRecordException;
use App\Services\SourceApi\ParsesSourceValues;
use App\Support\LaoAdministrativeCode;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * An item of LCIS GET /projects. area_ha and locations[] are being added to the LCIS list; until
 * they arrive, the area is unknown and the location falls back to the responsible province/district.
 */
final readonly class LcisProjectData
{
    use ParsesSourceValues;

    /**
     * @param  list<LcisLocationData>  $locations
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public ?string $projectCode,
        public ?string $activityName,
        public ?int $projectTypeId,
        public ?string $projectType,
        public ?string $status,
        public ?int $companyId,
        public ?string $companyName,
        public ?int $subsectorId,
        public ?string $subsectorName,
        public ?int $negotiationStatusId,
        public ?string $negotiationStatusName,
        public ?string $responsibleLevel,
        public ?int $responsibleProvinceId,
        public ?string $responsibleProvinceName,
        public ?int $responsibleDistrictId,
        public ?string $responsibleDistrictName,
        public ?float $areaHa,
        public array $locations,
        public ?CarbonImmutable $sourceUpdatedAt,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromApi(array $raw): self
    {
        $id = self::positiveIntOrNull($raw['id'] ?? null)
            ?? throw new InvalidSourceRecordException('LCIS project without a valid id.');

        $districtId = self::positiveIntOrNull($raw['responsibleDistrictId'] ?? null);
        // District-level projects send only the district; its code contains the province.
        $provinceId = self::positiveIntOrNull($raw['responsibleProvinceId'] ?? null) ?? LaoAdministrativeCode::provinceOfDistrict($districtId);
        $provinceName = self::stringOrNull($raw['responsible_province_name'] ?? null);
        $districtName = self::stringOrNull($raw['responsible_district_name'] ?? null);

        return new self(
            id: $id,
            projectCode: self::stringOrNull($raw['project_code'] ?? null),
            activityName: self::stringOrNull($raw['activity_name'] ?? null),
            projectTypeId: self::positiveIntOrNull($raw['id_project_type'] ?? null),
            projectType: self::stringOrNull($raw['project_type'] ?? null),
            status: self::stringOrNull($raw['status'] ?? null),
            companyId: self::positiveIntOrNull($raw['id_company'] ?? null),
            companyName: self::stringOrNull($raw['company_name'] ?? null),
            subsectorId: self::positiveIntOrNull($raw['id_subsect'] ?? null),
            subsectorName: self::stringOrNull($raw['subsect_name'] ?? null),
            negotiationStatusId: self::positiveIntOrNull($raw['id_status_neg'] ?? null),
            negotiationStatusName: self::stringOrNull($raw['status_neg_name'] ?? null),
            responsibleLevel: self::stringOrNull($raw['responsibleLevel'] ?? null),
            responsibleProvinceId: $provinceId,
            responsibleProvinceName: $provinceName,
            responsibleDistrictId: $districtId,
            responsibleDistrictName: $districtName,
            areaHa: self::floatOrNull($raw['area_ha'] ?? null),
            locations: self::locations($raw, $provinceId, $provinceName, $districtId, $districtName),
            sourceUpdatedAt: self::timestampOrNull($raw['updated_at'] ?? null),
            raw: $raw,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(CarbonInterface $syncedAt): array
    {
        return [
            'id' => $this->id,
            'project_code' => $this->projectCode,
            'activity_name' => $this->activityName,
            'project_type_id' => $this->projectTypeId,
            'project_type' => $this->projectType,
            'status' => $this->status,
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'subsector_id' => $this->subsectorId,
            'subsector_name' => $this->subsectorName,
            'negotiation_status_id' => $this->negotiationStatusId,
            'negotiation_status_name' => $this->negotiationStatusName,
            'responsible_level' => $this->responsibleLevel,
            'responsible_province_id' => $this->responsibleProvinceId,
            'responsible_province_name' => $this->responsibleProvinceName,
            'responsible_district_id' => $this->responsibleDistrictId,
            'responsible_district_name' => $this->responsibleDistrictName,
            'area_ha' => $this->areaHa,
            'key_fields_hash' => $this->keyFieldsHash(),
            'source_updated_at' => $this->sourceUpdatedAt,
            'raw' => json_encode($this->raw, JSON_UNESCAPED_UNICODE),
            'payload_hash' => self::payloadHash($this->raw),
            'synced_at' => $syncedAt,
            'missing_since' => null,
            'created_at' => $syncedAt,
            'updated_at' => $syncedAt,
        ];
    }

    /**
     * Hash of the fields that matching relies on; a change after a match is confirmed flags it for review.
     */
    public function keyFieldsHash(): string
    {
        return self::fieldsHash([
            $this->projectCode,
            $this->companyId,
            $this->companyName,
            $this->activityName,
            $this->areaHa,
            array_map(fn (LcisLocationData $location): array => $location->keyFields(), $this->locations),
        ]);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<LcisLocationData>
     */
    private static function locations(array $raw, ?int $provinceId, ?string $provinceName, ?int $districtId, ?string $districtName): array
    {
        if (is_array($raw['locations'] ?? null) && $raw['locations'] !== []) {
            return array_values(array_map(
                fn (array $location): LcisLocationData => LcisLocationData::fromApi($location),
                array_filter($raw['locations'], 'is_array'),
            ));
        }

        if ($provinceId === null) {
            return [];
        }

        return [new LcisLocationData($provinceId, $provinceName, $districtId, $districtName, null, null, true)];
    }
}
