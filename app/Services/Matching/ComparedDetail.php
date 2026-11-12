<?php

namespace App\Services\Matching;

/**
 * One row of the "other key fields" on the compare screen. differs is null when the two values
 * are not comparable (e.g. LCIS negotiation status vs AMIS contract status), so only real
 * differences get highlighted.
 */
final readonly class ComparedDetail
{
    public function __construct(
        public string $label,
        public ?string $lcis,
        public ?string $amis,
        public ?bool $differs = null,
    ) {}
}
