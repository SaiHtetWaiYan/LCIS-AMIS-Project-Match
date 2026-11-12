<?php

namespace Tests\Feature\Services\Matching;

use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Services\Matching\MatchPair;
use App\Services\Matching\MatchRule;
use App\Services\Matching\MatchScorer;
use App\Services\Matching\RuleResult;
use App\Services\Matching\Rules\AreaRule;
use App\Services\Matching\RuleValues;
use InvalidArgumentException;
use Tests\TestCase;

class MatchScorerTest extends TestCase
{
    public function test_total_is_the_weighted_average_over_rules_that_had_data(): void
    {
        config(['matching.rules' => [
            FixedScoreRule::class => 0.6,
            SecondFixedScoreRule::class => 0.2,
            NoDataRule::class => 0.2,
        ]]);

        $card = app(MatchScorer::class)->score($this->pair());

        $this->assertEqualsWithDelta((0.9 * 0.6 + 0.5 * 0.2) / 0.8, $card->total(), 0.000001);
        $this->assertSame(2, $card->rulesWithData());
        $this->assertSame(3, $card->ruleCount());
        $this->assertNull($card->outcome(NoDataRule::class)->score);
        $this->assertSame('fixed reason', $card->outcome(FixedScoreRule::class)->reason);
    }

    public function test_has_no_total_when_no_rule_had_data(): void
    {
        config(['matching.rules' => [NoDataRule::class => 1.0, AreaRule::class => 1.0]]);

        $card = app(MatchScorer::class)->score($this->pair());

        $this->assertNull($card->total());
        $this->assertSame(0, $card->rulesWithData());
    }

    public function test_a_rule_added_in_the_config_takes_part_without_other_changes(): void
    {
        config(['matching.rules' => [...config('matching.rules'), FixedScoreRule::class => 1.0]]);

        $card = app(MatchScorer::class)->score($this->pair());

        $this->assertSame(5, $card->ruleCount());
        $this->assertSame('Fixed', $card->outcome(FixedScoreRule::class)->label);
        $this->assertEqualsWithDelta(0.9, $card->total(), 0.000001);
    }

    public function test_rejects_a_configured_class_that_is_not_a_rule(): void
    {
        config(['matching.rules' => [self::class => 1.0]]);

        $this->expectException(InvalidArgumentException::class);

        app(MatchScorer::class)->score($this->pair());
    }

    private function pair(): MatchPair
    {
        return new MatchPair(
            LcisProject::factory()->make()->setRelation('locations', collect()),
            AmisContract::factory()->make()->setRelation('items', collect()),
            [],
        );
    }
}

class FixedScoreRule implements MatchRule
{
    use HasNoValues;

    public function label(): string
    {
        return 'Fixed';
    }

    public function evaluate(MatchPair $pair): ?RuleResult
    {
        return new RuleResult(0.9, 'fixed reason');
    }
}

class SecondFixedScoreRule implements MatchRule
{
    use HasNoValues;

    public function label(): string
    {
        return 'Second';
    }

    public function evaluate(MatchPair $pair): ?RuleResult
    {
        return new RuleResult(0.5, 'second reason');
    }
}

class NoDataRule implements MatchRule
{
    use HasNoValues;

    public function label(): string
    {
        return 'No data';
    }

    public function evaluate(MatchPair $pair): ?RuleResult
    {
        return null;
    }
}

trait HasNoValues
{
    public function values(MatchPair $pair): RuleValues
    {
        return new RuleValues([], []);
    }
}
