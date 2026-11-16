<?php

namespace Tests\Feature;

use App\Enums\SyncSource;
use App\Models\AmisCompany;
use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\LcisProject;
use App\Models\LcisProjectLocation;
use App\Models\ProjectMatch;
use App\Models\User;
use App\Services\Matching\MatchTermIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AmisContractControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/amis-contracts')->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function searchTerms(): array
    {
        return [
            'contract number' => ['5871/MTC'],
            'contract name' => ['ລານຈອດລົດ'],
            'legacy partner name' => ['ບີເອັມຊີ'],
            'linked company English name, case-insensitive' => ['samphanxay'],
            'linked company tax ID' => ['637767752000'],
        ];
    }

    #[DataProvider('searchTerms')]
    public function test_search_matches_number_name_partners_and_linked_companies(string $term): void
    {
        $contract = AmisContract::factory()->create(['contract_number' => '5871/MTC', 'contract_name' => 'ສຳປະທານລານຈອດລົດ', 'partner_names' => ['ບໍລິສັດບີເອັມຊີ']]);
        $contract->companies()->attach(AmisCompany::factory()->create(['name_eng' => 'Samphanxay Mining Co., Ltd', 'tax_id' => '637767752000']), ['shareholding_pct' => 60]);
        AmisContract::factory()->create(['contract_number' => '1200/OTHER', 'contract_name' => 'Other', 'partner_names' => ['Someone else']]);

        $response = $this->actingAs(User::factory()->amis()->create())->get('/amis-contracts?'.http_build_query(['search' => $term]));

        $response->assertOk();
        $response->assertSeeText('5871/MTC');
        $response->assertDontSeeText('1200/OTHER');
    }

    public function test_province_filter_uses_land_plots_and_falls_back_to_the_budget_province(): void
    {
        $plotInChampasak = AmisContract::factory()->create(['contract_number' => 'PLOT-16', 'from_province_id' => 1]);
        AmisContractItem::factory()->for($plotInChampasak, 'contract')->create(['province_id' => 16]);
        AmisContract::factory()->create(['contract_number' => 'NO-PLOTS-16', 'from_province_id' => 16]);
        $plotElsewhere = AmisContract::factory()->create(['contract_number' => 'PLOT-ELSEWHERE', 'from_province_id' => 16]);
        AmisContractItem::factory()->for($plotElsewhere, 'contract')->create(['province_id' => 1]);

        $response = $this->actingAs(User::factory()->create())->get('/amis-contracts?province=16');

        $response->assertOk();
        $response->assertSeeText(['PLOT-16', 'NO-PLOTS-16']);
        $response->assertDontSeeText('PLOT-ELSEWHERE');
    }

    public function test_filters_by_contract_type_and_status(): void
    {
        AmisContract::factory()->create(['contract_number' => 'RENTAL-ENDED', 'contract_type' => 'rental', 'status' => 'end']);
        AmisContract::factory()->create(['contract_number' => 'RENTAL-ACTIVE', 'contract_type' => 'rental', 'status' => 'nomal']);
        AmisContract::factory()->create(['contract_number' => 'CONCESSION-ENDED', 'contract_type' => 'concession', 'status' => 'end']);

        $response = $this->actingAs(User::factory()->create())->get('/amis-contracts?type=rental&status=end');

        $response->assertOk();
        $response->assertSeeText('RENTAL-ENDED');
        $response->assertDontSeeText(['RENTAL-ACTIVE', 'CONCESSION-ENDED']);
    }

    public function test_rejects_an_unknown_status_filter(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/amis-contracts?status=deleted');

        $response->assertRedirect(route('amis-contracts.index'));
        $response->assertSessionHasErrors('status');
    }

    public function test_show_page_lists_companies_land_plots_and_flags_unconvertible_units(): void
    {
        $contract = AmisContract::factory()->create(['contract_name' => 'ໂຄງການປູກກາເຟ', 'area_ha' => null]);
        $contract->companies()->attach(AmisCompany::factory()->create(['name_lao' => 'ບໍລິສັດ ສຳພັນໄຊ', 'tax_id' => '637767752000']), ['shareholding_pct' => 60]);
        AmisContractItem::factory()->for($contract, 'contract')->create(['land_use_detail' => 'ປູກກາເຟ', 'area' => 120.5, 'unit' => 'ເຮັກຕາ', 'area_ha' => 120.5]);
        AmisContractItem::factory()->for($contract, 'contract')->create(['land_use_detail' => 'ໂຮງງານ', 'area' => 30, 'unit' => 'ໄລ່', 'area_ha' => null]);

        $response = $this->actingAs(User::factory()->lcis()->create())->get("/amis-contracts/{$contract->id}");

        $response->assertOk();
        $response->assertSeeText(['ໂຄງການປູກກາເຟ', 'ບໍລິສັດ ສຳພັນໄຊ', '637767752000', '60%', 'ປູກກາເຟ', '120.50 ha', '30 ໄລ່', 'Unit not convertible']);
    }

    public function test_show_page_explains_a_legacy_contract_without_company_link(): void
    {
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດບີເອັມຊີ']]);

        $response = $this->actingAs(User::factory()->create())->get("/amis-contracts/{$contract->id}");

        $response->assertOk();
        $response->assertSeeText(['ບໍລິສັດບີເອັມຊີ', 'No company is linked in AMIS (legacy contract)']);
    }

    public function test_list_shows_the_top_suggestion_score(): void
    {
        $project = LcisProject::factory()->create(['project_code' => 'AGR-2024-0012', 'company_name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ', 'activity_name' => 'ປູກກາເຟ']);
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => 16, 'district_id' => 1601]);
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດສຳພັນໄຊ'], 'contract_name' => 'ສັນຍາ ປູກກາເຟ']);
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => 16, 'district_id' => 1601, 'land_use_detail' => null]);
        $this->rebuildMatchTerms();

        $response = $this->actingAs(User::factory()->create())->get('/amis-contracts');

        $response->assertOk();
        $response->assertSeeTextInOrder(['Best score', 'ສັນຍາ ປູກກາເຟ', '100%', 'Top suggestion', 'AGR-2024-0012']);
    }

    public function test_list_shows_the_score_of_a_proposed_pair_instead_of_a_suggestion(): void
    {
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດສຳພັນໄຊ'], 'contract_name' => 'ປູກກາເຟ']);
        LcisProject::factory()->create(['project_code' => 'BETTER-1', 'company_name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ', 'activity_name' => 'ປູກກາເຟ']);
        ProjectMatch::factory()->create([
            'amis_contract_id' => $contract->id,
            'lcis_project_id' => LcisProject::factory()->create(['project_code' => 'PAIRED-1', 'company_name' => 'ບໍລິສັດ ອື່ນ', 'activity_name' => 'ລ້ຽງປາ']),
        ]);
        $this->rebuildMatchTerms();

        $response = $this->actingAs(User::factory()->create())->get('/amis-contracts');

        $response->assertOk();
        $response->assertSeeTextInOrder(['This pair', 'PAIRED-1']);
        $response->assertDontSeeText(['Top suggestion', 'BETTER-1']);
    }

    private function rebuildMatchTerms(): void
    {
        app(MatchTermIndex::class)->rebuild(SyncSource::Lcis);
        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);
    }
}
