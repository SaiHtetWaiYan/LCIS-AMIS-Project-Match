<?php

namespace Tests\Feature\Services\Matching;

use App\Models\AmisCompany;
use App\Models\AmisContract;
use App\Models\LcisCompany;
use App\Models\LcisProject;
use App\Services\Matching\Rules\CompanyNameRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyNameRuleTest extends TestCase
{
    use BuildsMatchPairs;
    use RefreshDatabase;

    public function test_matches_a_lao_name_written_with_zero_width_spaces_and_another_legal_form(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ຮ່ວມທຶນສຳພັນໄຊ ມາຍນີ້ງ ຈຳກັດ']);
        $contract = AmisContract::factory()->create(['partner_names' => []]);
        $contract->companies()->attach(AmisCompany::factory()->create(['name_lao' => "ບໍ\u{200B}ລິ\u{200B}ສັດ ຮ່ວມ\u{200B}ທຶນ\u{200B}ສຳ\u{200B}ພັນ\u{200B}ໄຊ ມາຍ\u{200B}ນີ້ງ ຈໍາ\u{200B}ກັດຜູ້ດຽວ", 'name_eng' => null]));

        $result = app(CompanyNameRule::class)->evaluate($this->pairFor($project, $contract));

        $this->assertSame(1.0, $result->score);
        $this->assertStringContainsString('LCIS company “ບໍລິສັດ ຮ່ວມທຶນສຳພັນໄຊ ມາຍນີ້ງ ຈຳກັດ” ↔ AMIS company', $result->reason);
    }

    public function test_takes_the_best_pair_including_english_names_and_says_which_pair_matched(): void
    {
        $company = LcisCompany::factory()->create(['name' => 'ບໍລິສັດ ສຳພັນໄຊ', 'name_eng' => 'Samphanxay Mining Co., Ltd']);
        $project = LcisProject::factory()->create(['company_id' => $company->id, 'company_name' => 'ບໍລິສັດ ສຳພັນໄຊ']);
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດ ອື່ນໆ']]);
        $contract->companies()->attach(AmisCompany::factory()->create(['name_lao' => 'ບໍລິສັດ ຄູນຄຳ', 'name_eng' => 'SAMPHANXAY MINING SOLE CO.,LTD']));

        $result = app(CompanyNameRule::class)->evaluate($this->pairFor($project, $contract));

        $this->assertSame(1.0, $result->score);
        $this->assertSame('LCIS company (English) “Samphanxay Mining Co., Ltd” ↔ AMIS company (English) “SAMPHANXAY MINING SOLE CO.,LTD”', $result->reason);
    }

    public function test_uses_partner_names_of_a_legacy_contract_without_company_link(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ບີເອັມຊີ ຈຳກັດ']);
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດບີເອັມຊີ']]);

        $result = app(CompanyNameRule::class)->evaluate($this->pairFor($project, $contract));

        $this->assertSame(1.0, $result->score);
        $this->assertStringContainsString('AMIS partner “ບໍລິສັດບີເອັມຊີ”', $result->reason);
    }

    public function test_scores_unrelated_names_low(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ສຳພັນໄຊ']);
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດ ຄູນຄຳ']]);

        $result = app(CompanyNameRule::class)->evaluate($this->pairFor($project, $contract));

        $this->assertLessThan(0.2, $result->score);
    }

    public function test_has_no_score_when_the_contract_has_no_company_or_partner_name(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ສຳພັນໄຊ']);
        $contract = AmisContract::factory()->create(['partner_names' => []]);

        $this->assertNull(app(CompanyNameRule::class)->evaluate($this->pairFor($project, $contract)));
    }
}
