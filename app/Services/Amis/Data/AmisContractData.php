<?php

namespace App\Services\Amis\Data;

use App\Exceptions\SourceApi\InvalidSourceRecordException;
use App\Services\Amis\AreaUnitConverter;
use App\Services\SourceApi\ParsesSourceValues;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * An AMIS rent-land contract from GET /income/rent-land (the list returns the full object).
 *
 * AMIS spells contract "contact" (contact_id, contact_name, contact_number, contact_date_*); the
 * status "nomal" is kept as sent. partner[] holds names; legacy contracts store them as
 * "+"-separated text that AMIS already splits. Anything in partner[] that is not a plain name
 * (newer contracts store company links as JSON) is ignored.
 */
final readonly class AmisContractData
{
    use ParsesSourceValues;

    /**
     * @param  list<string>  $partnerNames
     * @param  list<AmisContractItemData>  $items
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public ?string $contractType,
        public ?int $businessTypeId,
        public ?string $businessTypeName,
        public ?string $contractNumber,
        public ?string $contractName,
        public array $partnerNames,
        public ?string $status,
        public ?string $startsOn,
        public ?string $endsOn,
        public ?int $fromProvinceId,
        public array $items,
        public ?CarbonImmutable $sourceUpdatedAt,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromApi(array $raw, AreaUnitConverter $units): self
    {
        $id = self::positiveIntOrNull($raw['contact_id'] ?? null)
            ?? throw new InvalidSourceRecordException('AMIS contract without a valid contact_id.');

        $partnerProvinceId = is_array($raw['partner_address'] ?? null)
            ? self::positiveIntOrNull($raw['partner_address']['province_id'] ?? null)
            : null;

        $items = [];

        foreach (is_array($raw['items'] ?? null) ? $raw['items'] : [] as $item) {
            $itemData = is_array($item) ? AmisContractItemData::fromApi($item, $partnerProvinceId, $units) : null;

            if ($itemData !== null) {
                $items[] = $itemData;
            }
        }

        return new self(
            id: $id,
            contractType: self::stringOrNull($raw['contract_type'] ?? null),
            businessTypeId: self::positiveIntOrNull($raw['business_type_id'] ?? null),
            businessTypeName: self::stringOrNull($raw['business_type_name'] ?? null),
            contractNumber: self::stringOrNull($raw['contact_number'] ?? null),
            contractName: self::stringOrNull($raw['contact_name'] ?? null),
            partnerNames: self::partnerNames($raw['partner'] ?? null),
            status: self::stringOrNull($raw['status'] ?? null),
            startsOn: self::dateOrNull($raw['contact_date_start'] ?? null),
            endsOn: self::dateOrNull($raw['contact_date_end'] ?? null),
            fromProvinceId: self::positiveIntOrNull($raw['from_province_id'] ?? null),
            items: $items,
            sourceUpdatedAt: self::timestampOrNull($raw['updated_at'] ?? null),
            raw: $raw,
        );
    }

    /**
     * Total area in hectares, or null when no item has an area or any item's unit is unknown.
     */
    public function areaHa(): ?float
    {
        $itemsWithArea = array_filter($this->items, fn (AmisContractItemData $item): bool => $item->area !== null && $item->area > 0);

        if ($itemsWithArea === [] || $this->unknownUnits() !== []) {
            return null;
        }

        return array_sum(array_map(fn (AmisContractItemData $item): float => $item->areaHa, $itemsWithArea));
    }

    /**
     * Units (as sent) of items whose area could not be converted.
     *
     * @return list<string>
     */
    public function unknownUnits(): array
    {
        $units = [];

        foreach ($this->items as $item) {
            if ($item->hasUnknownUnit()) {
                $units[] = $item->unit ?? '';
            }
        }

        return $units;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(CarbonInterface $syncedAt): array
    {
        return [
            'id' => $this->id,
            'contract_type' => $this->contractType,
            'business_type_id' => $this->businessTypeId,
            'business_type_name' => $this->businessTypeName,
            'contract_number' => $this->contractNumber,
            'contract_name' => $this->contractName,
            'partner_names' => json_encode($this->partnerNames, JSON_UNESCAPED_UNICODE),
            'status' => $this->status,
            'starts_on' => $this->startsOn,
            'ends_on' => $this->endsOn,
            'from_province_id' => $this->fromProvinceId,
            'area_ha' => $this->areaHa(),
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
            $this->contractNumber,
            $this->contractName,
            $this->partnerNames,
            array_map(fn (AmisContractItemData $item): array => $item->keyFields(), $this->items),
        ]);
    }

    /**
     * @return list<string>
     */
    private static function partnerNames(mixed $partner): array
    {
        $names = [];

        foreach (is_array($partner) ? $partner : [] as $name) {
            $name = is_string($name) ? trim($name) : '';

            if ($name !== '' && ! str_starts_with($name, '{') && ! str_starts_with($name, '[') && ! ctype_digit($name)) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }
}
