<?php

namespace App\Services\Amis\Data;

use App\Exceptions\SourceApi\InvalidSourceRecordException;
use App\Services\SourceApi\ParsesSourceValues;
use Carbon\CarbonInterface;

/**
 * An item of AMIS GET /companies. rent_lands[] gives the contract ids this company is a partner on,
 * with its shareholding percentage — the only place AMIS links contracts to companies.
 */
final readonly class AmisCompanyData
{
    use ParsesSourceValues;

    /**
     * @param  array<int, ?float>  $contractShares  shareholding_pct keyed by contract (contact) id
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public ?string $nameLao,
        public ?string $nameEng,
        public ?string $companyType,
        public ?string $taxId,
        public ?int $provinceId,
        public ?int $districtId,
        public ?string $districtName,
        public ?string $village,
        public bool $isActive,
        public array $contractShares,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromApi(array $raw): self
    {
        $id = self::positiveIntOrNull($raw['id'] ?? null)
            ?? throw new InvalidSourceRecordException('AMIS company without a valid id.');

        $contractShares = [];

        foreach (is_array($raw['rent_lands'] ?? null) ? $raw['rent_lands'] : [] as $rentLand) {
            $contractId = is_array($rentLand) ? self::positiveIntOrNull($rentLand['contact_id'] ?? null) : null;

            if ($contractId !== null) {
                $contractShares[$contractId] = self::floatOrNull($rentLand['shareholding_pct'] ?? null);
            }
        }

        return new self(
            id: $id,
            nameLao: self::stringOrNull($raw['name_lao'] ?? null),
            nameEng: self::stringOrNull($raw['name_eng'] ?? null),
            companyType: self::stringOrNull($raw['company_type'] ?? null),
            taxId: self::stringOrNull($raw['tax_id'] ?? null),
            provinceId: self::positiveIntOrNull($raw['province_id'] ?? null),
            districtId: self::positiveIntOrNull($raw['district_id'] ?? null),
            districtName: self::stringOrNull($raw['district_name'] ?? null),
            village: self::stringOrNull($raw['village'] ?? null),
            isActive: ($raw['is_active'] ?? true) !== false,
            contractShares: $contractShares,
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
            'name_lao' => $this->nameLao,
            'name_eng' => $this->nameEng,
            'company_type' => $this->companyType,
            'tax_id' => $this->taxId,
            'province_id' => $this->provinceId,
            'district_id' => $this->districtId,
            'district_name' => $this->districtName,
            'village' => $this->village,
            'is_active' => $this->isActive,
            'raw' => json_encode($this->raw, JSON_UNESCAPED_UNICODE),
            'payload_hash' => self::payloadHash($this->raw),
            'synced_at' => $syncedAt,
            'missing_since' => null,
            'created_at' => $syncedAt,
            'updated_at' => $syncedAt,
        ];
    }
}
