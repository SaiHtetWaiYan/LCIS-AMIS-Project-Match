<?php

namespace App\Services\Matching;

/**
 * One way of comparing an LCIS project with an AMIS contract. Rules are listed with their
 * weights in config/matching.php; screens and the workflow only see their results.
 */
interface MatchRule
{
    /**
     * Short name shown next to the score.
     */
    public function label(): string;

    /**
     * Score 0–1 with a readable reason, or null when either side lacks the data.
     */
    public function evaluate(MatchPair $pair): ?RuleResult;
}
