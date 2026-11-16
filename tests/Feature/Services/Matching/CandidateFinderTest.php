<?php

namespace Tests\Feature\Services\Matching;

use App\Enums\SyncSource;
use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\LcisProject;
use App\Models\LcisProjectLocation;
use App\Services\Matching\Candidate;
use App\Services\Matching\CandidateFinder;
use App\Services\Matching\MatchTermIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateFinderTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranks_the_matching_contract_first_among_similar_ones(): void
    {
        $project = $this->project('ບໍລິສັດ ສຳພັນໄຊ ມາຍນີ້ງ ຈຳກັດ', 'ຂຸດຄົ້ນແຮ່ທອງ', 16, 1601);
        $match = $this->contract(['ບໍ​ລິ​ສັດ ສຳ​ພັນ​ໄຊ ມາຍ​ນີ້ງ ຈໍາ​ກັດ'], 'ສັນຍາ ຂຸດຄົ້ນແຮ່ທອງ', 16, 1601);
        $sameCompanyOtherActivity = $this->contract(['ບໍລິສັດ ສຳພັນໄຊ ມາຍນີ້ງ'], 'ປູກກາເຟ', 1, 104);
        $sameActivityOtherCompany = $this->contract(['ບໍລິສັດ ຄູນຄຳ'], 'ຂຸດຄົ້ນແຮ່ທອງ', 14, 1401);
        $this->index();

        $candidates = app(CandidateFinder::class)->forLcisProject($project);

        $ids = $this->ids($candidates);
        $this->assertSame($match->id, $ids[0]);
        // Both near-misses are capped at the same total, so their order is not defined.
        $this->assertEqualsCanonicalizing([$sameCompanyOtherActivity->id, $sameActivityOtherCompany->id], array_slice($ids, 1));
        $this->assertSame(3, $candidates[0]->score->rulesWithData());
        $this->assertEqualsWithDelta(1.0, $candidates[0]->score->total(), 0.0001);
    }

    public function test_includes_records_that_only_share_a_district(): void
    {
        $project = $this->project('ບໍລິສັດ ສຳພັນໄຊ', 'ປູກກາເຟ', 16, 1601);
        $neighbour = $this->contract(['ບໍລິສັດ ຄູນຄຳ'], 'ລ້ຽງງົວ', 16, 1601);
        $this->contract(['ບໍລິສັດ ອື່ນ'], 'ລ້ຽງປາ', 1, 104);
        $this->index();

        $candidates = app(CandidateFinder::class)->forLcisProject($project);

        $this->assertSame([$neighbour->id], $this->ids($candidates));
    }

    public function test_never_suggests_a_record_missing_from_its_source(): void
    {
        $project = $this->project('ບໍລິສັດ ສຳພັນໄຊ', 'ປູກກາເຟ', 16, 1601);
        $gone = $this->contract(['ບໍລິສັດ ສຳພັນໄຊ'], 'ປູກກາເຟ', 16, 1601);
        $gone->forceFill(['missing_since' => now()])->save();
        $this->index();

        $this->assertSame([], app(CandidateFinder::class)->forLcisProject($project));
    }

    public function test_returns_at_most_the_configured_number_of_candidates(): void
    {
        config(['matching.candidates.limit' => 3]);
        $project = $this->project('ບໍລິສັດ ສຳພັນໄຊ', 'ປູກກາເຟ', 16, 1601);

        foreach (range(1, 5) as $i) {
            $this->contract(['ບໍລິສັດ ສຳພັນໄຊ '.$i], 'ປູກກາເຟ', 16, 1601);
        }

        $this->index();

        $this->assertCount(3, app(CandidateFinder::class)->forLcisProject($project));
    }

    public function test_suggests_lcis_projects_for_an_amis_contract(): void
    {
        $contract = $this->contract(['ບໍລິສັດບີເອັມຊີ'], 'ສຳປະທານລານຈອດລົດ', 1, 104);
        $match = $this->project('ບໍລິສັດ ບີເອັມຊີ ຈຳກັດ', 'ລານຈອດລົດ', 1, 104);
        $this->project('ບໍລິສັດ ຄູນຄຳ', 'ປູກກາເຟ', 16, 1601);
        $this->index();

        $candidates = app(CandidateFinder::class)->forAmisContract($contract);

        $this->assertSame($match->id, $candidates[0]->record->id);
        $this->assertCount(1, $candidates);
    }

    private function project(string $company, string $activity, int $provinceId, int $districtId): LcisProject
    {
        $project = LcisProject::factory()->create(['company_name' => $company, 'activity_name' => $activity]);
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => $provinceId, 'district_id' => $districtId]);

        return $project;
    }

    /**
     * @param  list<string>  $partners
     */
    private function contract(array $partners, string $name, int $provinceId, int $districtId): AmisContract
    {
        $contract = AmisContract::factory()->create(['partner_names' => $partners, 'contract_name' => $name]);
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => $provinceId, 'district_id' => $districtId, 'land_use_detail' => null]);

        return $contract;
    }

    private function index(): void
    {
        app(MatchTermIndex::class)->rebuild(SyncSource::Lcis);
        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);
    }

    /**
     * @param  list<Candidate>  $candidates
     * @return list<int>
     */
    private function ids(array $candidates): array
    {
        return array_map(fn (Candidate $candidate): int => $candidate->record->id, $candidates);
    }
}
