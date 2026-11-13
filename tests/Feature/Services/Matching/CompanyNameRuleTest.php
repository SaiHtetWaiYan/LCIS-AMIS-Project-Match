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

    public function test_tells_apart_hydropower_companies_that_only_share_sector_words(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ໄຟຟ້ານໍ້າໂມ້ 2 ຈຳກັດ']);
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດ ໄຟຟ້ານ້ຳງຽບ 2 ຈຳກັດ']]);

        $result = app(CompanyNameRule::class)->evaluate($this->pairFor($project, $contract));

        $this->assertLessThan(0.3, $result->score);
    }

    public function test_scores_names_with_different_project_numbers_low_and_says_why(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ໄຟຟ້າ ນໍ້າກົງ 3 ຈຳກັດ']);
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດ ໄຟຟ້ານ້ຳກົງ 1 ຈຳກັດ']]);

        $result = app(CompanyNameRule::class)->evaluate($this->pairFor($project, $contract));

        $this->assertLessThan(0.35, $result->score);
        $this->assertStringEndsWith('(different project numbers)', $result->reason);
    }

    public function test_still_matches_the_same_numbered_project_typed_differently(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ໄຟຟ້າ ນໍ້າກົງ 1 ຈຳກັດ']);
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດ ໄຟຟ້ານ້ຳກົງ 1 ຈຳກັດ']]);

        $this->assertSame(1.0, app(CompanyNameRule::class)->evaluate($this->pairFor($project, $contract))->score);
    }

    public function test_caps_the_total_when_the_companies_clearly_differ(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ຮ່ວາຟາດ - ລາວ ຈຳກັດ']);
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດ ຂຸດຄົ້ນບໍ່ແຮ່ ຕຸ່ມຮ່ວງ ລາວ-ຈີນ ຈຳກັດ']]);

        $result = app(CompanyNameRule::class)->evaluate($this->pairFor($project, $contract));

        $this->assertLessThan(0.2, $result->score);
        $this->assertSame(0.4, $result->capsTotalAt);
    }

    public function test_does_not_cap_matching_companies(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ຊັນເປເປີ້ ສະຫວັນນະເຂດ ຈຳກັດ']);
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດຊັນເປເປີ້ໂຮນດິ້ງຈໍາກັດ']]);

        $this->assertNull(app(CompanyNameRule::class)->evaluate($this->pairFor($project, $contract))->capsTotalAt);
    }
}
