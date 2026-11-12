<?php

namespace App\Services\Matching\Rules;

use App\Services\Matching\MatchPair;
use App\Services\Matching\MatchRule;
use App\Services\Matching\RuleResult;

/**
 * Scores the most similar pair of normalised texts of one kind (pg_trgm similarity, 0–1) and
 * names which pair it was.
 */
abstract class TextSimilarityRule implements MatchRule
{
    abstract protected function kind(): string;

    public function evaluate(MatchPair $pair): ?RuleResult
    {
        $match = $pair->bestTextMatch($this->kind());

        if ($match === null) {
            return null;
        }

        return new RuleResult($match->score, __('matching.reasons.text', [
            'lcis_source' => __('matching.sources.'.$match->lcisSource),
            'lcis_text' => $match->lcisText,
            'amis_source' => __('matching.sources.'.$match->amisSource),
            'amis_text' => $match->amisText,
        ]));
    }
}
