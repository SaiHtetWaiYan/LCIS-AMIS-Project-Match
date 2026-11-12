<?php

namespace App\Services\Matching;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Runs every rule configured in config/matching.php on a pair.
 */
class MatchScorer
{
    /**
     * @var array<class-string<MatchRule>, MatchRule>|null
     */
    private ?array $rules = null;

    public function __construct(private readonly Container $container) {}

    public function score(MatchPair $pair): ScoreCard
    {
        $outcomes = [];

        foreach (config('matching.rules') as $ruleClass => $weight) {
            $rule = $this->rule($ruleClass);
            $result = $rule->evaluate($pair);
            $outcomes[] = new RuleOutcome($ruleClass, $rule->label(), (float) $weight, $result?->score, $result?->reason);
        }

        return new ScoreCard($outcomes);
    }

    private function rule(string $ruleClass): MatchRule
    {
        if (! is_subclass_of($ruleClass, MatchRule::class)) {
            throw new InvalidArgumentException("{$ruleClass} in config/matching.php does not implement MatchRule.");
        }

        return $this->rules[$ruleClass] ??= $this->container->make($ruleClass);
    }
}
