<?php

namespace Tests\Feature\Services\Matching;

use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\LcisProject;
use App\Services\Matching\Rules\ActivityRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityRuleTest extends TestCase
{
    use BuildsMatchPairs;
    use RefreshDatabase;

    public function test_matches_the_contract_name_ignoring_generic_words(): void
    {
        $project = LcisProject::factory()->create(['activity_name' => 'ໂຄງການ ປູກກາເຟ']);
        $contract = AmisContract::factory()->create(['contract_name' => 'ສັນຍາ ປູກກາເຟ']);

        $result = app(ActivityRule::class)->evaluate($this->pairFor($project, $contract));

        $this->assertSame(1.0, $result->score);
        $this->assertSame('LCIS activity “ໂຄງການ ປູກກາເຟ” ↔ AMIS contract name “ສັນຍາ ປູກກາເຟ”', $result->reason);
    }

    public function test_matches_a_land_use_detail_when_it_is_closer_than_the_contract_name(): void
    {
        $project = LcisProject::factory()->create(['activity_name' => 'ຂຸດຄົ້ນ ແລະ ປຸງແຕ່ງແຮ່ເຫຼັກ']);
        $contract = AmisContract::factory()->create(['contract_name' => 'ສັນຍາສຳປະທານທີ່ດິນ']);
        AmisContractItem::factory()->for($contract, 'contract')->create(['land_use_detail' => 'ຂຸດຄົ້້ນ ແລະ ປຸງແຕ່ງ ແຮ່ເຫລັກ']);

        $result = app(ActivityRule::class)->evaluate($this->pairFor($project, $contract));

        $this->assertSame(1.0, $result->score);
        $this->assertStringContainsString('AMIS land use', $result->reason);
    }

    public function test_has_no_score_without_an_lcis_activity(): void
    {
        $project = LcisProject::factory()->create(['activity_name' => null]);
        $contract = AmisContract::factory()->create(['contract_name' => 'ປູກກາເຟ']);

        $this->assertNull(app(ActivityRule::class)->evaluate($this->pairFor($project, $contract)));
    }

    public function test_caps_the_total_when_the_activities_have_almost_nothing_in_common(): void
    {
        $project = LcisProject::factory()->create(['activity_name' => 'ພັດທະນາໂຄງການທ່າບົກ ທ່ານາແລ້ງ ແລະ ເຂດໂລຊີດສະຕິກ']);
        $contract = AmisContract::factory()->create(['contract_name' => 'ໂຄງການ ສວນສະໜຸກປາກເຊວັນເດີແລນ']);

        $result = app(ActivityRule::class)->evaluate($this->pairFor($project, $contract));

        $this->assertLessThan(0.2, $result->score);
        $this->assertSame(0.4, $result->capsTotalAt);
    }

    public function test_does_not_cap_similar_activities(): void
    {
        $project = LcisProject::factory()->create(['activity_name' => 'ປູກຕົ້ນໄມ້ອຸດສາຫະກຳ']);
        $contract = AmisContract::factory()->create(['contract_name' => 'ໂຄງການປູກໄມ້ອຸດສາຫະກຳ']);

        $this->assertNull(app(ActivityRule::class)->evaluate($this->pairFor($project, $contract))->capsTotalAt);
    }
}
