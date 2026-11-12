<?php

namespace App\Services\Lcis\Data;

use App\Exceptions\SourceApi\InvalidSourceRecordException;
use App\Services\SourceApi\ParsesSourceValues;
use Carbon\CarbonInterface;

/**
 * An item of LCIS GET /companies: { id, name, name_eng, tel, email, country }.
 */
final readonly class LcisCompanyData
{
    use ParsesSourceValues;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $nameEng,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromApi(array $raw): self
    {
        $id = self::positiveIntOrNull($raw['id'] ?? null)
            ?? throw new InvalidSourceRecordException('LCIS company without a valid id.');

        return new self($id, self::stringOrNull($raw['name'] ?? null), self::stringOrNull($raw['name_eng'] ?? null), $raw);
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(CarbonInterface $syncedAt): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_eng' => $this->nameEng,
            'raw' => json_encode($this->raw, JSON_UNESCAPED_UNICODE),
            'payload_hash' => self::payloadHash($this->raw),
            'synced_at' => $syncedAt,
            'missing_since' => null,
            'created_at' => $syncedAt,
            'updated_at' => $syncedAt,
        ];
    }
}
