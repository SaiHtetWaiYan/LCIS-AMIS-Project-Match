<?php

namespace Tests\Feature;

use App\Enums\SyncSource;
use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\LcisProject;
use App\Models\LcisProjectLocation;
use App\Models\ProjectMatch;
use App\Models\User;
use App\Services\Matching\CandidateFinder;
use App\Services\Matching\MatchTermIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lcis_project_page_suggests_amis_contracts_with_score_and_reasons(): void
    {
        [$project, $contract] = $this->matchingPair();
        AmisContract::factory()->create(['contract_number' => 'UNRELATED-1', 'partner_names' => ['ບໍລິສັດ ອື່ນ'], 'contract_name' => 'ລ້ຽງປາ']);
        $this->index();

        $response = $this->actingAs(User::factory()->lcis()->create())->get("/lcis-projects/{$project->id}");

        $response->assertOk();
        $response->assertSeeTextInOrder(['Suggested AMIS contracts', '5871/MTC', '100%', '3 of 4 rules had data']);
        $response->assertSeeText(['Company name', 'LCIS company “ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ” ↔ AMIS partner', 'Same province', 'No data on one side']);
        $response->assertSeeText('Compare');
        $response->assertDontSeeText('UNRELATED-1');
    }

    public function test_amis_contract_page_suggests_lcis_projects(): void
    {
        [$project, $contract] = $this->matchingPair();
        $this->index();

        $response = $this->actingAs(User::factory()->amis()->create())->get("/amis-contracts/{$contract->id}");

        $response->assertOk();
        $response->assertSeeTextInOrder(['Suggested LCIS projects', 'AGR-2024-0012']);
    }

    public function test_amis_contract_page_shows_its_proposed_pair_above_the_suggestions(): void
    {
        [$project, $contract] = $this->matchingPair();
        ProjectMatch::factory()->create(['lcis_project_id' => $project->id, 'amis_contract_id' => $contract->id]);
        $this->index();

        $response = $this->actingAs(User::factory()->amis()->create())->get("/amis-contracts/{$contract->id}");

        $response->assertOk();
        $response->assertSeeTextInOrder(['Suggested LCIS projects', 'This pair', 'AGR-2024-0012', '100%', 'Proposed', 'No suggestions. Try the search below.']);
    }

    public function test_lcis_project_page_shows_its_matched_pair_even_when_the_contract_left_amis(): void
    {
        [$project, $contract] = $this->matchingPair();
        $contract->forceFill(['missing_since' => now()])->save();
        ProjectMatch::factory()->matched()->create(['lcis_project_id' => $project->id, 'amis_contract_id' => $contract->id]);
        $this->index();

        $response = $this->actingAs(User::factory()->lcis()->create())->get("/lcis-projects/{$project->id}");

        $response->assertOk();
        $response->assertSeeTextInOrder(['Suggested AMIS contracts', 'This pair', '5871/MTC']);
    }

    public function test_equally_scored_suggestions_are_ordered_by_id(): void
    {
        [$project, $contract] = $this->matchingPair();
        $twin = AmisContract::factory()->create(['contract_number' => '5872/MTC', 'partner_names' => $contract->partner_names, 'contract_name' => $contract->contract_name]);
        AmisContractItem::factory()->for($twin, 'contract')->create(['province_id' => 16, 'district_id' => 1601, 'land_use_detail' => null]);
        $this->index();

        $finder = app(CandidateFinder::class);
        $ids = array_map(fn ($candidate): int => $candidate->record->id, $finder->forLcisProject($project));

        $this->assertSame([$contract->id, $twin->id], $ids);
        $this->assertSame($contract->id, $finder->forLcisProject($project, 1)[0]->record->id);
    }

    public function test_manual_search_finds_and_scores_records_that_were_not_suggested(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ສຳພັນໄຊ', 'activity_name' => 'ປູກກາເຟ']);
        AmisContract::factory()->create(['contract_number' => 'HIDDEN-77', 'contract_name' => 'ໂຮງແຮມ', 'partner_names' => ["ບໍ\u{200B}ລິ\u{200B}ສັດ ທ່ອງທ່ຽວ"]]);
        $this->index();

        $response = $this->actingAs(User::factory()->create())->get("/lcis-projects/{$project->id}?q=".urlencode('ບໍລິສັດ ທ່ອງທ່ຽວ'));

        $response->assertOk();
        $response->assertSeeTextInOrder(['No suggestions. Try the search below.', 'Results for “ບໍລິສັດ ທ່ອງທ່ຽວ”', 'HIDDEN-77', 'of 4 rules had data']);
    }

    public function test_manual_search_says_when_nothing_is_found(): void
    {
        $contract = AmisContract::factory()->create();
        $this->index();

        $response = $this->actingAs(User::factory()->create())->get("/amis-contracts/{$contract->id}?q=nothing-like-this");

        $response->assertOk();
        $response->assertSeeText('Nothing found for “nothing-like-this”.');
    }

    public function test_escapes_record_text_in_the_score_reasons(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'Evil <script>alert(1)</script> Co']);
        AmisContract::factory()->create(['partner_names' => ['Evil <script>alert(1)</script> Co']]);
        $this->index();

        $response = $this->actingAs(User::factory()->create())->get("/lcis-projects/{$project->id}");

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    /**
     * @return array{LcisProject, AmisContract}
     */
    private function matchingPair(): array
    {
        $project = LcisProject::factory()->create(['project_code' => 'AGR-2024-0012', 'company_name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ', 'activity_name' => 'ປູກກາເຟ']);
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => 16, 'district_id' => 1601]);
        $contract = AmisContract::factory()->create(['contract_number' => '5871/MTC', 'partner_names' => ['ບໍລິສັດສຳພັນໄຊ'], 'contract_name' => 'ສັນຍາ ປູກກາເຟ']);
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => 16, 'district_id' => 1601, 'land_use_detail' => null]);

        return [$project, $contract];
    }

    private function index(): void
    {
        app(MatchTermIndex::class)->rebuild(SyncSource::Lcis);
        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);
    }
}
