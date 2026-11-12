<?php

namespace Tests\Feature;

use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_the_matching_fields_first_and_then_the_other_key_fields(): void
    {
        $project = LcisProject::factory()->create(['project_code' => 'AGR-0012', 'project_type' => 'ກະສິກຳ']);
        $contract = AmisContract::factory()->create(['contract_number' => '5871/MTC', 'business_type_name' => 'ບໍ່ແຮ່']);

        $response = $this->actingAs(User::factory()->lcis()->create())->get("/compare?lcis={$project->id}&amis={$contract->id}");

        $response->assertOk();
        $response->assertSeeTextInOrder([
            'Matching fields', 'AGR-0012', '5871/MTC',
            'Company name', 'Activity', 'Project location', 'Area (ha)',
            'Other key fields', 'Sector', 'Differs', 'ກະສິກຳ', 'ບໍ່ແຮ່',
        ]);
    }

    public function test_a_side_user_can_propose_the_pair_from_here(): void
    {
        [$project, $contract] = [LcisProject::factory()->create(), AmisContract::factory()->create()];

        $response = $this->actingAs(User::factory()->amis()->create())->get("/compare?lcis={$project->id}&amis={$contract->id}");

        $response->assertSeeText(['Propose this pair', 'Propose match']);
    }

    public function test_explains_why_a_pair_cannot_be_proposed_when_a_record_is_in_another_match(): void
    {
        $existing = ProjectMatch::factory()->create();
        $contract = AmisContract::factory()->create();

        $response = $this->actingAs(User::factory()->amis()->create())->get("/compare?lcis={$existing->lcis_project_id}&amis={$contract->id}");

        $response->assertOk();
        $response->assertSeeText(['This LCIS project is already in another match:', "Proposed #{$existing->id}"]);
        $response->assertSeeText("You cannot propose this pair: This LCIS project is already in match #{$existing->id} (Proposed).");
        $response->assertDontSeeText('Propose match');
    }

    public function test_admins_get_no_proposal_section(): void
    {
        [$project, $contract] = [LcisProject::factory()->create(), AmisContract::factory()->create()];

        $response = $this->actingAs(User::factory()->admin()->create())->get("/compare?lcis={$project->id}&amis={$contract->id}");

        $response->assertOk();
        $response->assertDontSeeText('Propose this pair');
    }

    public function test_a_pair_already_proposed_opens_on_its_match_page(): void
    {
        $match = ProjectMatch::factory()->create();

        $response = $this->actingAs(User::factory()->amis()->create())->get("/compare?lcis={$match->lcis_project_id}&amis={$match->amis_contract_id}");

        $response->assertRedirect(route('matches.show', $match));
    }

    public function test_lists_earlier_proposals_of_the_same_pair_with_their_reason(): void
    {
        $rejected = ProjectMatch::factory()->rejected()->create(['rejection_reason' => 'Another plot of the same company.']);

        $response = $this->actingAs(User::factory()->lcis()->create())->get("/compare?lcis={$rejected->lcis_project_id}&amis={$rejected->amis_contract_id}");

        $response->assertSeeTextInOrder(['Earlier proposals of this pair', "Match #{$rejected->id} · Rejected", 'Another plot of the same company.']);
    }

    public function test_needs_an_existing_project_and_contract(): void
    {
        $response = $this->actingAs(User::factory()->lcis()->create())->get('/compare?lcis=404');

        $response->assertSessionHasErrors(['lcis', 'amis']);
    }

    public function test_the_match_page_shows_the_pair_side_by_side_with_its_history(): void
    {
        $match = ProjectMatch::factory()->matched()->create();

        $response = $this->actingAs(User::factory()->lcis()->create())->get("/matches/{$match->id}");

        $response->assertOk();
        $response->assertSeeTextInOrder(["Match #{$match->id}", 'Matched', 'Matching fields', 'Other key fields', 'LCIS confirmation', 'History']);
        $response->assertDontSeeText('Propose this pair');
    }
}
