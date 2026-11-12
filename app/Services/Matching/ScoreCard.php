<?php

namespace App\Services\Matching;

/**
 * The combined score of a pair: the weighted average over the rules that had data, plus how
 * many rules that was, so a high total resting on one rule is visibly weak.
 */
final readonly class ScoreCard
{
    /**
     * @param  list<RuleOutcome>  $outcomes
     */
    public function __construct(public array $outcomes) {}

    public function total(): ?float
    {
        $weighted = 0.0;
        $weights = 0.0;

        foreach ($this->outcomes as $outcome) {
            if ($outcome->hasData()) {
                $weighted += $outcome->score * $outcome->weight;
                $weights += $outcome->weight;
            }
        }

        return $weights > 0 ? $weighted / $weights : null;
    }

    public function rulesWithData(): int
    {
        return count(array_filter($this->outcomes, fn (RuleOutcome $outcome): bool => $outcome->hasData()));
    }

    public function ruleCount(): int
    {
        return count($this->outcomes);
    }

    public function outcome(string $ruleClass): ?RuleOutcome
    {
        foreach ($this->outcomes as $outcome) {
            if ($outcome->rule === $ruleClass) {
                return $outcome;
            }
        }

        return null;
    }
}
