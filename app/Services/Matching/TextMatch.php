<?php

namespace App\Services\Matching;

/**
 * The most similar pair of texts of one kind (names or activities) between the two records.
 */
final readonly class TextMatch
{
    public function __construct(
        public float $score,
        public string $lcisSource,
        public string $lcisText,
        public string $amisSource,
        public string $amisText,
        public bool $numbersDiffer = false,
    ) {}
}
