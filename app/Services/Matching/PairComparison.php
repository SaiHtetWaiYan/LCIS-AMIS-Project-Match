<?php

namespace App\Services\Matching;

use App\Models\AmisContract;
use App\Models\LcisProject;

/**
 * Everything the compare screen shows about a pair: the score, each rule's values side by side,
 * and the other key fields.
 */
final readonly class PairComparison
{
    /**
     * @param  array<class-string<MatchRule>, RuleValues>  $ruleValues
     * @param  list<ComparedDetail>  $details
     */
    public function __construct(
        public LcisProject $project,
        public AmisContract $contract,
        public ScoreCard $score,
        public array $ruleValues,
        public array $details,
    ) {}

    public function valuesFor(RuleOutcome $outcome): RuleValues
    {
        return $this->ruleValues[$outcome->rule] ?? new RuleValues([], []);
    }
}
