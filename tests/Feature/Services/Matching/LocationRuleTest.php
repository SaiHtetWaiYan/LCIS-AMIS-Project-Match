<?php

namespace Tests\Feature\Services\Matching;

use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\LcisProject;
use App\Models\LcisProjectLocation;
use App\Services\Matching\MatchPair;
use App\Services\Matching\Rules\LocationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocationRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{array{int, ?int, ?string}, array{int, ?int, ?string}, float}>
     */
    public static function locations(): array
    {
        return [
            'same province, district and village' => [[16, 1601, 'ບ້ານ ໂພນສະອາດ'], [16, 1601, 'ໂພນສະອາດ'], 1.0],
            'same province and district' => [[16, 1601, 'ໂພນສະອາດ'], [16, 1601, 'ນາແກ'], 0.8],
            'same province only' => [[16, 1601, null], [16, 1605, null], 0.5],
            'same village name in another province' => [[1, 104, 'ພະໂພ'], [16, 1601, 'ພະໂພ'], 0.0],
        ];
    }

    /**
     * @param  array{int, ?int, ?string}  $lcis
     * @param  array{int, ?int, ?string}  $amis
     */
    #[DataProvider('locations')]
    public function test_scores_province_district_and_village_agreement(array $lcis, array $amis, float $expected): void
    {
        $project = LcisProject::factory()->create();
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => $lcis[0], 'district_id' => $lcis[1], 'village_name' => $lcis[2]]);
        $contract = AmisContract::factory()->create();
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => $amis[0], 'district_id' => $amis[1], 'village' => $amis[2]]);

        $result = app(LocationRule::class)->evaluate($this->pair($project, $contract));

        $this->assertEqualsWithDelta($expected, $result->score, 0.0001);
    }

    public function test_takes_the_best_combination_of_locations_and_plots(): void
    {
        $project = LcisProject::factory()->create();
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => 1, 'district_id' => 104]);
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => 16, 'district_id' => 1601, 'district_name' => 'ປາກເຊ', 'village_name' => null]);
        $contract = AmisContract::factory()->create();
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => 14, 'district_id' => 1401]);
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => 16, 'district_id' => 1601, 'district_name' => 'ປາກເຊ', 'village' => null]);

        $result = app(LocationRule::class)->evaluate($this->pair($project, $contract));

        $this->assertEqualsWithDelta(0.8, $result->score, 0.0001);
        $this->assertStringContainsString('same district (ປາກເຊ)', $result->reason);
    }

    public function test_says_when_the_lcis_location_is_only_the_responsible_office(): void
    {
        $project = LcisProject::factory()->create();
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => 16, 'district_id' => 1601, 'is_fallback' => true]);
        $contract = AmisContract::factory()->create();
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => 16, 'district_id' => 1601]);

        $result = app(LocationRule::class)->evaluate($this->pair($project, $contract));

        $this->assertStringContainsString('[LCIS responsible office, not land location]', $result->reason);
    }

    public function test_has_no_score_when_the_contract_has_no_plot_with_a_province(): void
    {
        $project = LcisProject::factory()->create();
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => 16]);
        $contract = AmisContract::factory()->create();
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => null]);

        $this->assertNull(app(LocationRule::class)->evaluate($this->pair($project, $contract)));
    }

    private function pair(LcisProject $project, AmisContract $contract): MatchPair
    {
        return new MatchPair($project->fresh('locations'), $contract->fresh('items'), []);
    }
}
