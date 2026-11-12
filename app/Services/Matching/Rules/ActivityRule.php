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
}
