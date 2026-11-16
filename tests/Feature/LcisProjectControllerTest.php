<?php

namespace Tests\Feature;

use App\Enums\SyncSource;
use App\Enums\UserSide;
use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\LcisCompany;
use App\Models\LcisProject;
use App\Models\LcisProjectLocation;
use App\Models\ProjectMatch;
use App\Models\User;
use App\Services\Matching\BestScoreRecorder;
use App\Services\Matching\MatchTermIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LcisProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{UserSide}>
     */
    public static function allSides(): array
    {
        return [
            'lcis' => [UserSide::Lcis],
            'amis' => [UserSide::Amis],
            'admin' => [UserSide::Admin],
        ];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/lcis-projects')->assertRedirect(route('login'));
    }

    #[DataProvider('allSides')]
    public function test_every_side_can_browse_lcis_projects(UserSide $side): void
    {
        LcisProject::factory()->create(['activity_name' => 'ໂຄງການປູກກາເຟ']);

        $response = $this->actingAs(User::factory()->create(['side' => $side]))->get('/lcis-projects');

        $response->assertOk();
        $response->assertSeeText('ໂຄງການປູກກາເຟ');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function searchTerms(): array
    {
        return [
            'project code' => ['AGR-77'],
            'activity in Lao' => ['ກາເຟ'],
            'company name in Lao' => ['ສຳພັນໄຊ'],
            'company name in English, case-insensitive' => ['samphanxay'],
        ];
    }

    #[DataProvider('searchTerms')]
    public function test_search_matches_code_activity_and_company_names(string $term): void
    {
        $company = LcisCompany::factory()->create(['name_eng' => 'Samphanxay Mining Co., Ltd']);
        LcisProject::factory()->create(['project_code' => 'AGR-77', 'activity_name' => 'ໂຄງການປູກກາເຟ', 'company_id' => $company->id, 'company_name' => 'ບໍລິສັດ ສຳພັນໄຊ']);
        LcisProject::factory()->create(['project_code' => 'MIN-01', 'activity_name' => 'Other activity', 'company_name' => 'Other company']);

        $response = $this->actingAs(User::factory()->create())->get('/lcis-projects?'.http_build_query(['search' => $term]));

        $response->assertOk();
        $response->assertSeeText('AGR-77');
        $response->assertDontSeeText('MIN-01');
    }

    public function test_search_ignores_zero_width_spaces_and_lao_spelling_variants(): void
    {
        LcisProject::factory()->create(['project_code' => 'ZWSP-1', 'company_name' => "ບໍ\u{200B}ລິ\u{200B}ສັດ ຄູນ\u{200B}ຄໍາ ຈໍາ\u{200B}ກັດ"]);
        LcisProject::factory()->create(['project_code' => 'OTHER-2', 'company_name' => 'ບໍລິສັດ ສຳພັນໄຊ']);
        app(MatchTermIndex::class)->rebuild(SyncSource::Lcis);

        $response = $this->actingAs(User::factory()->create())->get('/lcis-projects?'.http_build_query(['search' => 'ຄູນຄຳ']));

        $response->assertOk();
        $response->assertSeeText('ZWSP-1');
        $response->assertDontSeeText('OTHER-2');
    }

    public function test_search_treats_percent_as_a_literal_character(): void
    {
        LcisProject::factory()->create(['project_code' => 'AGR-77']);

        $response = $this->actingAs(User::factory()->create())->get('/lcis-projects?search=%25');

        $response->assertOk();
        $response->assertDontSeeText('AGR-77');
        $response->assertSeeText('No LCIS projects match these filters.');
    }

    public function test_filters_by_location_province_project_type_and_source_presence(): void
    {
        $wanted = LcisProject::factory()->create(['project_code' => 'WANTED', 'project_type_id' => 2, 'project_type' => 'Mining']);
        LcisProjectLocation::factory()->for($wanted, 'project')->create(['province_id' => 16]);
        $otherProvince = LcisProject::factory()->create(['project_code' => 'OTHER-PROVINCE', 'project_type_id' => 2]);
        LcisProjectLocation::factory()->for($otherProvince, 'project')->create(['province_id' => 1]);
        $otherType = LcisProject::factory()->create(['project_code' => 'OTHER-TYPE', 'project_type_id' => 1]);
        LcisProjectLocation::factory()->for($otherType, 'project')->create(['province_id' => 16]);
        $missing = LcisProject::factory()->missing()->create(['project_code' => 'MISSING', 'project_type_id' => 2]);
        LcisProjectLocation::factory()->for($missing, 'project')->create(['province_id' => 16]);

        $response = $this->actingAs(User::factory()->create())->get('/lcis-projects?province=16&type=2&source=present');

        $response->assertOk();
        $response->assertSeeText('WANTED');
        $response->assertDontSeeText(['OTHER-PROVINCE', 'OTHER-TYPE', 'MISSING']);
    }

    public function test_invalid_filters_return_to_the_unfiltered_list(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/lcis-projects?province=abc&source=everything');

        $response->assertRedirect(route('lcis-projects.index'));
        $response->assertSessionHasErrors(['province', 'source']);
    }

    public function test_show_page_lists_key_fields_locations_and_raw_data(): void
    {
        $company = LcisCompany::factory()->create(['name_eng' => 'Samphanxay Mining Co., Ltd']);
        $project = LcisProject::factory()->missing()->create([
            'company_id' => $company->id,
            'company_name' => 'ບໍລິສັດ ສຳພັນໄຊ',
            'raw' => ['subsect_name' => 'Coffee roasting'],
        ]);
        LcisProjectLocation::factory()->for($project, 'project')->create(['district_name' => 'ປາກເຊ', 'is_fallback' => true]);

        $response = $this->actingAs(User::factory()->amis()->create())->get("/lcis-projects/{$project->id}");

        $response->assertOk();
        $response->assertSeeText(['ບໍລິສັດ ສຳພັນໄຊ', 'Samphanxay Mining Co., Ltd', 'ປາກເຊ', 'Coffee roasting', 'Not sent by LCIS yet']);
        $response->assertSeeText('LCIS does not send land locations yet');
        $response->assertSeeText('Missing from source since');
    }

    public function test_list_shows_the_top_suggestion_score(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ', 'activity_name' => 'ປູກກາເຟ']);
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => 16, 'district_id' => 1601]);
        $contract = AmisContract::factory()->create(['contract_number' => '5871/MTC', 'partner_names' => ['ບໍລິສັດສຳພັນໄຊ'], 'contract_name' => 'ສັນຍາ ປູກກາເຟ']);
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => 16, 'district_id' => 1601, 'land_use_detail' => null]);
        $this->scoreAll();

        $response = $this->actingAs(User::factory()->create())->get('/lcis-projects');

        $response->assertOk();
        $response->assertSeeTextInOrder(['Best score', 'ປູກກາເຟ', '100%', 'Top suggestion', '5871/MTC']);
    }

    public function test_list_shows_the_score_of_a_proposed_pair_instead_of_a_suggestion(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ', 'activity_name' => 'ປູກກາເຟ']);
        AmisContract::factory()->create(['contract_number' => 'BETTER-1', 'partner_names' => ['ບໍລິສັດສຳພັນໄຊ'], 'contract_name' => 'ປູກກາເຟ']);
        ProjectMatch::factory()->create([
            'lcis_project_id' => $project->id,
            'amis_contract_id' => AmisContract::factory()->create(['contract_number' => 'PAIRED-1', 'partner_names' => ['ບໍລິສັດ ອື່ນ'], 'contract_name' => 'ລ້ຽງປາ']),
        ]);
        $this->scoreAll();

        $response = $this->actingAs(User::factory()->create())->get('/lcis-projects');

        $response->assertOk();
        $response->assertSeeTextInOrder(['This pair', 'PAIRED-1']);
        $response->assertDontSeeText(['Top suggestion', 'BETTER-1']);
    }

    public function test_escapes_source_text_on_the_list(): void
    {
        LcisProject::factory()->create(['activity_name' => '<script>alert(1)</script>']);

        $response = $this->actingAs(User::factory()->create())->get('/lcis-projects');

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_sorts_by_highest_best_score_and_filters_by_minimum_score(): void
    {
        LcisProject::factory()->create(['project_code' => 'SCORE-40', 'best_score' => 0.4, 'best_scored_at' => now()]);
        LcisProject::factory()->create(['project_code' => 'SCORE-90', 'best_score' => 0.9, 'best_scored_at' => now()]);
        LcisProject::factory()->create(['project_code' => 'NOT-SCORED']);
        LcisProject::factory()->create(['project_code' => 'SCORE-60', 'best_score' => 0.6, 'best_scored_at' => now()]);
        $user = User::factory()->create();

        $sorted = $this->actingAs($user)->get('/lcis-projects?sort=score');

        $sorted->assertOk();
        $sorted->assertSeeTextInOrder(['SCORE-90', '90%', 'SCORE-60', '60%', 'SCORE-40', '40%', 'NOT-SCORED', 'Not scored yet']);

        $filtered = $this->actingAs($user)->get('/lcis-projects?min_score=50&sort=score');

        $filtered->assertOk();
        $filtered->assertSeeTextInOrder(['SCORE-90', 'SCORE-60']);
        $filtered->assertDontSeeText(['SCORE-40', 'NOT-SCORED']);
    }

    private function scoreAll(): void
    {
        app(MatchTermIndex::class)->rebuild(SyncSource::Lcis);
        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);
        app(BestScoreRecorder::class)->refreshAll();
    }
}
