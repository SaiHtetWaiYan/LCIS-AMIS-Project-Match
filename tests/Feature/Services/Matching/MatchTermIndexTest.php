<?php

namespace Tests\Feature\Services\Matching;

use App\Enums\SyncSource;
use App\Models\AmisCompany;
use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\LcisCompany;
use App\Models\LcisProject;
use App\Services\Matching\MatchTermIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatchTermIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_indexes_normalised_lcis_names_and_activity_without_duplicates(): void
    {
        $company = LcisCompany::factory()->create(['name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈໍາກັດ', 'name_eng' => 'Samphanxay Co., Ltd']);
        $project = LcisProject::factory()->create(['company_id' => $company->id, 'company_name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ', 'activity_name' => 'ໂຄງການ ປູກກາເຟ']);

        $count = app(MatchTermIndex::class)->rebuild(SyncSource::Lcis);

        $this->assertSame(3, $count);
        $this->assertEqualsCanonicalizing(
            [['name', 'lcis_company', 'ສຳພັນໄຊ'], ['name', 'lcis_company_eng', 'samphanxay'], ['activity', 'lcis_activity', 'ປູກກາເຟ']],
            DB::table('match_terms')->where('record_id', $project->id)->get()->map(fn ($row) => [$row->kind, $row->source, $row->text_norm])->all(),
        );
    }

    public function test_indexes_amis_partners_linked_companies_and_land_use_but_not_missing_companies(): void
    {
        $contract = AmisContract::factory()->create(['contract_name' => 'ສັນຍາ ປູກກາເຟ', 'partner_names' => ['ບໍລິສັດ ບີເອັມຊີ']]);
        $contract->companies()->attach(AmisCompany::factory()->create(['name_lao' => 'ບໍລິສັດ ຄູນຄຳ', 'name_eng' => null]));
        $contract->companies()->attach(AmisCompany::factory()->missing()->create(['name_lao' => 'ບໍລິສັດ ເກົ່າ', 'name_eng' => null]));
        AmisContractItem::factory()->for($contract, 'contract')->create(['land_use_detail' => 'ໂຮງງານປຸງແຕ່ງໄມ້']);

        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);

        $this->assertEqualsCanonicalizing(
            ['amis_contract_name:ປູກກາເຟ', 'amis_company_lao:ຄູນຄຳ', 'amis_partner:ບີເອັມຊີ', 'amis_land_use:ໄມ້'],
            DB::table('match_terms')->where('side', 'amis')->get()->map(fn ($row) => $row->source.':'.$row->text_norm)->all(),
        );
    }

    public function test_rebuilding_one_side_leaves_the_other_side_alone(): void
    {
        LcisProject::factory()->create();
        AmisContract::factory()->create();
        app(MatchTermIndex::class)->rebuild(SyncSource::Lcis);
        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);
        $lcisRows = DB::table('match_terms')->where('side', 'lcis')->count();

        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);

        $this->assertSame($lcisRows, DB::table('match_terms')->where('side', 'lcis')->count());
    }

    public function test_the_test_database_can_compare_lao_text(): void
    {
        $this->assertTrue(app(MatchTermIndex::class)->databaseSupportsLao());
    }

    public function test_indexes_a_contract_named_after_its_company_as_a_name_not_an_activity(): void
    {
        $contract = AmisContract::factory()->create(['contract_name' => "ບໍ\u{200B}ລິ\u{200B}ສັດແຮ່ເຫຼັກໂກເອໂກ", 'partner_names' => []]);
        AmisContractItem::factory()->for($contract, 'contract')->create(['land_use_detail' => 'ໂຮງແຮມປາກຊັນ']);

        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);

        $this->assertEqualsCanonicalizing(
            ['name:amis_contract_name', 'activity:amis_land_use'],
            DB::table('match_terms')->where('side', 'amis')->get()->map(fn ($row) => $row->kind.':'.$row->source)->all(),
        );
    }

    public function test_recognises_company_names_including_spelling_variants(): void
    {
        $this->assertTrue(MatchTermIndex::isCompanyName('ບໍລິສັດ ໂຄລາວ ຣີສອດ ຈໍາກັດ'));
        $this->assertTrue(MatchTermIndex::isCompanyName('ກຸ່ມບໍລິສັດ ໄຊຊະນະ'));
        $this->assertTrue(MatchTermIndex::isCompanyName('ບໍລີສັດ ຄຳໝັ້ນ'));
        $this->assertFalse(MatchTermIndex::isCompanyName('ໂຄງການປູກກາເຟ'));
        $this->assertFalse(MatchTermIndex::isCompanyName(null));
    }
}
