<?php

namespace App\Services\Matching;

/**
 * What one rule said about a pair. score and reason are null when the rule had no data.
 */
final readonly class RuleOutcome
{
    public function __construct(
        public string $rule,
        public string $label,
        public float $weight,
        public ?float $score,
        public ?string $reason,
    ) {}

    public function hasData(): bool
    {
        return $this->score !== null;
    }
}
