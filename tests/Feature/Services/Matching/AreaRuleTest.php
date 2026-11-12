<?php

namespace Tests\Feature\Services\Matching;

use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Services\Matching\MatchPair;
use App\Services\Matching\Rules\AreaRule;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AreaRuleTest extends TestCase
{
    /**
     * @return array<string, array{float, float, float}>
     */
    public static function areas(): array
    {
        return [
            'identical' => [120.0, 120.0, 1.0],
            'within 5%' => [100.0, 96.0, 1.0],
            'halfway between 5% and 50%' => [100.0, 72.5, 0.5],
            '50% apart' => [100.0, 50.0, 0.0],
            'far apart' => [10.0, 500.0, 0.0],
        ];
    }

    #[DataProvider('areas')]
    public function test_scores_by_relative_difference(float $lcisArea, float $amisArea, float $expected): void
    {
        $result = (new AreaRule)->evaluate($this->pair($lcisArea, $amisArea));

        $this->assertEqualsWithDelta($expected, $result->score, 0.0001);
    }

    public function test_reason_shows_both_areas_in_hectares(): void
    {
        $result = (new AreaRule)->evaluate($this->pair(120, 121));

        $this->assertSame('LCIS 120.00 ha vs AMIS 121.00 ha (1% apart)', $result->reason);
    }

    public function test_has_no_score_until_lcis_sends_an_area(): void
    {
        $this->assertNull((new AreaRule)->evaluate($this->pair(null, 121)));
    }

    public function test_uses_thresholds_from_the_config(): void
    {
        config(['matching.area.full_score_within' => 0.10, 'matching.area.zero_score_from' => 0.20]);

        $this->assertEqualsWithDelta(0.5, (new AreaRule)->evaluate($this->pair(100, 85))->score, 0.0001);
    }

    private function pair(?float $lcisArea, ?float $amisArea): MatchPair
    {
        return new MatchPair(
            LcisProject::factory()->make(['area_ha' => $lcisArea]),
            AmisContract::factory()->make(['area_ha' => $amisArea]),
            [],
        );
    }
}
