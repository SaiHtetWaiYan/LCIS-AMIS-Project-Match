<?php

namespace App\Services\Matching;

final readonly class RuleResult
{
    public float $score;

    /**
     * @param  float|null  $capsTotalAt  the most the pair's total may score, whatever the other rules say
     */
    public function __construct(float $score, public string $reason, public ?float $capsTotalAt = null)
    {
        $this->score = max(0.0, min(1.0, $score));
    }
}
