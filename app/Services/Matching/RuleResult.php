<?php

namespace App\Services\Matching;

final readonly class RuleResult
{
    public float $score;

    public function __construct(float $score, public string $reason)
    {
        $this->score = max(0.0, min(1.0, $score));
    }
}
