<?php

namespace App\Services\Matching\Rules;

use App\Services\Matching\MatchPair;
use App\Services\Matching\MatchTermIndex;
use App\Services\Matching\RuleResult;

/**
 * LCIS activity name against the AMIS contract name and each land plot's land-use detail.
 */
class ActivityRule extends TextSimilarityRule
{
    public function label(): string
    {
        return __('matching.rules.activity');
    }

    /**
     * Activities with almost nothing in common cap the total: one company often runs several
     * projects, so a matching company name alone must not make a strong suggestion.
     */
    public function evaluate(MatchPair $pair): ?RuleResult
    {
        $result = parent::evaluate($pair);

        if ($result === null || $result->score >= (float) config('matching.caps.activity_below')) {
            return $result;
        }

        return new RuleResult($result->score, $result->reason, (float) config('matching.caps.total_at_most'));
    }

    protected function kind(): string
    {
        return MatchTermIndex::KIND_ACTIVITY;
    }
}
