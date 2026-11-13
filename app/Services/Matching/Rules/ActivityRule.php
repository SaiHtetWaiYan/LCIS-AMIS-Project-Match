<?php

namespace App\Services\Matching\Rules;

use App\Services\Matching\MatchTermIndex;

/**
 * LCIS activity name against the AMIS contract name and each land plot's land-use detail.
 */
class ActivityRule extends TextSimilarityRule
{
    public function label(): string
    {
        return __('matching.rules.activity');
    }

    protected function kind(): string
    {
        return MatchTermIndex::KIND_ACTIVITY;
    }

    /**
     * One company often runs several projects, so a matching company name alone must not make a
     * strong suggestion when the activities have almost nothing in common.
     */
    protected function capBelow(): ?float
    {
        return (float) config('matching.caps.activity_below');
    }
}
