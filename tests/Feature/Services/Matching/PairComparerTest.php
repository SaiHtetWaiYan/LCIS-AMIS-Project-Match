<?php

namespace Tests\Feature\Services\Matching;

use App\Models\AmisCompany;
use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\LcisCompany;
use App\Models\LcisProject;
use App\Models\LcisProjectLocation;
use App\Services\Matching\ComparedDetail;
use App\Services\Matching\PairComparer;
use App\Services\Matching\PairComparison;
use App\Services\Matching\Rules\AreaRule;
use App\Services\Matching\Rules\CompanyNameRule;
use App\Services\Matching\Rules\LocationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PairComparerTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_the_company_names_of_both_sides_with_where_each_comes_from(): void
    {
        $company = LcisCompany::factory()->create(['name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ', 'name_eng' => 'Samphanxay Co., Ltd']);
        $project = LcisProject::factory()->create(['company_id' => $company->id, 'company_name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ']);
        $contract = AmisContract::factory()->create(['partner_names' => ['ທ່ານ ສົມພອນ']]);
        $contract->companies()->attach(AmisCompany::factory()->create(['name_lao' => 'ບໍລິສັດ ສຳພັນໄຊ', 'name_eng' => null]), ['shareholding_pct' => 100]);

        $values = $this->compare($project, $contract)->ruleValues[CompanyNameRule::class];

        $this->assertSame([
            ['text' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ', 'note' => null],
            ['text' => 'Samphanxay Co., Ltd', 'note' => 'English'],
        ], $values->lcis);
        $this->assertSame([
            ['text' => 'ບໍລິສັດ ສຳພັນໄຊ', 'note' => 'Linked company'],
            ['text' => 'ທ່ານ ສົມພອນ', 'note' => 'Partner'],
        ], $values->amis);
    }

    public function test_shows_locations_and_areas_of_both_sides(): void
    {
        $project = LcisProject::factory()->create(['area_ha' => 120]);
        LcisProjectLocation::factory()->create(['lcis_project_id' => $project->id, 'province_id' => 1, 'district_name' => 'ສີສັດຕະນາກ', 'village_name' => 'ພະໂພ', 'is_fallback' => true]);
        $contract = AmisContract::factory()->create(['area_ha' => 121]);
        AmisContractItem::factory()->count(2)->create(['amis_contract_id' => $contract->id, 'province_id' => 1, 'district_name' => 'ໄຊເສດຖາ', 'village' => 'ໂພນສະອາດ']);

        $comparison = $this->compare($project, $contract);

        $this->assertSame([['text' => 'ນະຄອນຫຼວງວຽງຈັນ · ສີສັດຕະນາກ · ພະໂພ', 'note' => 'Responsible office']], $comparison->ruleValues[LocationRule::class]->lcis);
        $this->assertSame([['text' => 'ນະຄອນຫຼວງວຽງຈັນ · ໄຊເສດຖາ · ໂພນສະອາດ', 'note' => null]], $comparison->ruleValues[LocationRule::class]->amis);
        $this->assertSame([['text' => '120.00 ha', 'note' => null]], $comparison->ruleValues[AreaRule::class]->lcis);
        $this->assertSame([['text' => '121.00 ha', 'note' => '2 plots']], $comparison->ruleValues[AreaRule::class]->amis);
    }

    public function test_has_a_score_and_values_for_every_configured_rule(): void
    {
        $comparison = $this->compare(LcisProject::factory()->create(), AmisContract::factory()->create());

        $this->assertCount(count(config('matching.rules')), $comparison->score->outcomes);
        $this->assertSame(array_keys(config('matching.rules')), array_keys($comparison->ruleValues));
    }

    /**
     * @return array<string, array{?string, ?string, ?bool}>
     */
    public static function sectors(): array
    {
        return [
            'same sector' => ['ກະສິກຳ', 'ກະສິກຳ', false],
            'same sector, different spacing' => ['ບໍລິການ ແລະ ທ່ອງທ່ຽວ', 'ບໍລິການແລະທ່ອງທ່ຽວ', false],
            'different sector' => ['ກະສິກຳ', 'ບໍ່ແຮ່', true],
            'AMIS has no business type' => ['ກະສິກຳ', null, null],
        ];
    }

    #[DataProvider('sectors')]
    public function test_flags_a_sector_difference_only_when_both_sides_record_a_sector(string $lcisSector, ?string $amisSector, ?bool $differs): void
    {
        $comparison = $this->compare(
            LcisProject::factory()->create(['project_type' => $lcisSector]),
            AmisContract::factory()->create(['business_type_name' => $amisSector]),
        );

        $this->assertSame($differs, $this->detail($comparison, 'Sector')->differs);
    }

    public function test_flags_a_different_administering_province(): void
    {
        $project = LcisProject::factory()->create(['responsible_province_id' => 1]);

        $sameProvince = $this->compare($project, AmisContract::factory()->create(['from_province_id' => 1]));
        $otherProvince = $this->compare($project, AmisContract::factory()->create(['from_province_id' => 8]));
        $centralProject = $this->compare(LcisProject::factory()->create(['responsible_province_id' => null]), AmisContract::factory()->create());

        $this->assertFalse($this->detail($sameProvince, 'Administering office')->differs);
        $this->assertTrue($this->detail($otherProvince, 'Administering office')->differs);
        $this->assertNull($this->detail($centralProject, 'Administering office')->differs);
    }

    public function test_does_not_compare_fields_the_systems_record_differently(): void
    {
        $comparison = $this->compare(LcisProject::factory()->create(), AmisContract::factory()->create());

        $this->assertNull($this->detail($comparison, 'Status')->differs);
        $this->assertSame('2024-01-01 → 2029-01-01', $this->detail($comparison, 'Contract period')->amis);
        $this->assertNull($this->detail($comparison, 'Contract period')->lcis);
    }

    private function compare(LcisProject $project, AmisContract $contract): PairComparison
    {
        return app(PairComparer::class)->compare($project, $contract);
    }

    private function detail(PairComparison $comparison, string $label): ComparedDetail
    {
        return collect($comparison->details)->firstOrFail(fn (ComparedDetail $detail): bool => $detail->label === $label);
    }
}
