<?php

namespace App\Services\Matching\Rules;

use App\Services\Matching\MatchPair;
use App\Services\Matching\MatchRule;
use App\Services\Matching\RuleResult;

/**
 * Compares total areas in hectares by relative difference |a − b| / max(a, b): full score up to
 * full_score_within, falling linearly to 0 at zero_score_from (config/matching.php).
 */
class AreaRule implements MatchRule
{
    public function label(): string
    {
        return __('matching.rules.area');
    }

    public function evaluate(MatchPair $pair): ?RuleResult
    {
        $lcisArea = $pair->project->area_ha !== null ? (float) $pair->project->area_ha : null;
        $amisArea = $pair->contract->area_ha !== null ? (float) $pair->contract->area_ha : null;

        if ($lcisArea === null || $amisArea === null || max($lcisArea, $amisArea) <= 0) {
            return null;
        }

        $difference = abs($lcisArea - $amisArea) / max($lcisArea, $amisArea);

        return new RuleResult($this->scoreFor($difference), __('matching.reasons.area', [
            'lcis' => number_format($lcisArea, 2),
            'amis' => number_format($amisArea, 2),
            'percent' => (int) round($difference * 100),
        ]));
    }

    private function scoreFor(float $difference): float
    {
        $fullWithin = (float) config('matching.area.full_score_within');
        $zeroFrom = (float) config('matching.area.zero_score_from');

        if ($difference <= $fullWithin) {
            return 1.0;
        }

        if ($difference >= $zeroFrom) {
            return 0.0;
        }

        return 1 - ($difference - $fullWithin) / ($zeroFrom - $fullWithin);
    }
}
