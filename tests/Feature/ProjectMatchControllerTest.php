<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Enums\SyncSource;
use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\LcisProject;
use App\Models\LcisProjectLocation;
use App\Models\ProjectMatch;
use App\Models\User;
use App\Services\Matches\MatchCardinality;
use App\Services\Matching\MatchTermIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMatchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_lcis_user_proposes_a_pair_from_the_suggestions(): void
    {
        $lcisUser = User::factory()->lcis()->create();
        $project = LcisProject::factory()->create();
        $contract = AmisContract::factory()->create();

        $response = $this->actingAs($lcisUser)->post('/matches', ['lcis_project_id' => $project->id, 'amis_contract_id' => $contract->id]);

        $match = ProjectMatch::sole();
        $response->assertRedirect(route('matches.show', $match));
        $response->assertSessionHas('status', 'Match proposed. It now waits for confirmation from the AMIS side.');
        $this->assertSame(MatchStatus::Proposed, $match->status);
    }

    public function test_forbids_admins_from_proposing(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->post('/matches', [
            'lcis_project_id' => LcisProject::factory()->create()->id,
            'amis_contract_id' => AmisContract::factory()->create()->id,
        ]);

        $response->assertForbidden();
        $this->assertSame(0, ProjectMatch::count());
    }

    public function test_rejects_a_proposal_for_records_that_do_not_exist(): void
    {
        $response = $this->actingAs(User::factory()->lcis()->create())->post('/matches', ['lcis_project_id' => 999, 'amis_contract_id' => 998]);

        $response->assertSessionHasErrors(['lcis_project_id', 'amis_contract_id']);
    }

    public function test_a_proposal_that_loses_a_race_returns_with_a_friendly_error(): void
    {
        $this->app->instance(MatchCardinality::class, new class extends MatchCardinality
        {
            public function conflict(LcisProject $project, AmisContract $contract): ?string
            {
                return null;
            }
        });
        $existing = ProjectMatch::factory()->create();

        $response = $this->actingAs(User::factory()->amis()->create())->from('/lcis-projects')->post('/matches', [
            'lcis_project_id' => $existing->lcis_project_id,
            'amis_contract_id' => AmisContract::factory()->create()->id,
        ]);

        $response->assertRedirect('/lcis-projects');
        $response->assertSessionHas('error', 'Someone else proposed a match for one of these records a moment ago. Reload the page to see it.');
    }

    public function test_the_other_side_sees_confirm_and_reject_while_the_proposers_side_does_not(): void
    {
        $match = ProjectMatch::factory()->create();

        $this->actingAs(User::factory()->amis()->create())->get("/matches/{$match->id}")
            ->assertOk()
            ->assertSeeText(['Confirm match', 'Reject', 'Proposed', 'History']);
        $this->actingAs(User::factory()->lcis()->create())->get("/matches/{$match->id}")
            ->assertOk()
            ->assertDontSeeText(['Confirm match', 'Withdraw proposal']);
        $this->actingAs($match->proposer)->get("/matches/{$match->id}")
            ->assertSeeText('Withdraw proposal')
            ->assertDontSeeText('Confirm match');
    }

    public function test_confirming_via_http_matches_the_pair(): void
    {
        $match = ProjectMatch::factory()->create();

        $response = $this->actingAs(User::factory()->amis()->create())->post("/matches/{$match->id}/confirm");

        $response->assertRedirect(route('matches.show', $match));
        $response->assertSessionHas('status', 'Match confirmed by both sides.');
        $this->assertSame(MatchStatus::Matched, $match->refresh()->status);
    }

    public function test_confirming_from_the_same_side_is_forbidden_with_the_reason(): void
    {
        $match = ProjectMatch::factory()->create();

        $response = $this->actingAs(User::factory()->lcis()->create())->post("/matches/{$match->id}/confirm");

        $response->assertForbidden();
        $response->assertSeeText('Your side has already confirmed this match');
        $this->assertSame(MatchStatus::Proposed, $match->refresh()->status);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        $match = ProjectMatch::factory()->create();

        $response = $this->actingAs(User::factory()->amis()->create())->post("/matches/{$match->id}/reject", ['reason' => '']);

        $response->assertSessionHasErrors(['reason' => 'The reason field is required.']);
        $this->assertSame(MatchStatus::Proposed, $match->refresh()->status);
    }

    public function test_only_an_admin_unmatches_and_must_give_a_reason(): void
    {
        $match = ProjectMatch::factory()->matched()->create();

        $this->actingAs(User::factory()->amis()->create())->post("/matches/{$match->id}/unmatch", ['reason' => 'Wrong'])->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post("/matches/{$match->id}/unmatch", ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs(User::factory()->admin()->create())->post("/matches/{$match->id}/unmatch", ['reason' => 'Wrong contract'])->assertSessionHas('status');

        $this->assertSame(MatchStatus::Unmatched, $match->refresh()->status);
    }

    public function test_withdrawing_someone_elses_proposal_is_forbidden(): void
    {
        $match = ProjectMatch::factory()->create();

        $this->actingAs(User::factory()->lcis()->create())->post("/matches/{$match->id}/withdraw")->assertForbidden();

        $this->assertSame(MatchStatus::Proposed, $match->refresh()->status);
    }

    public function test_the_match_page_shows_the_history_and_review_reasons(): void
    {
        $match = ProjectMatch::factory()->matched()->needsReview()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->get("/matches/{$match->id}");

        $response->assertOk();
        $response->assertSeeText(['Needs review', 'key fields of the AMIS contract changed after confirmation', 'Mark as reviewed', 'Unmatch']);
    }

    public function test_suggestions_skip_records_in_an_active_pair_and_offer_propose_to_side_users_only(): void
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ສຳພັນໄຊ']);
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => 16, 'district_id' => 1601]);
        $free = $this->contract('FREE-1', 'ບໍລິສັດ ສຳພັນໄຊ');
        $taken = $this->contract('TAKEN-1', 'ບໍລິສັດ ສຳພັນໄຊ');
        ProjectMatch::factory()->create(['amis_contract_id' => $taken->id]);
        app(MatchTermIndex::class)->rebuild(SyncSource::Lcis);
        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);

        $this->actingAs(User::factory()->amis()->create())->get("/lcis-projects/{$project->id}")
            ->assertOk()
            ->assertSeeTextInOrder(['Suggested AMIS contracts', 'FREE-1', 'Propose match'])
            ->assertDontSeeText('TAKEN-1');
        $this->actingAs(User::factory()->admin()->create())->get("/lcis-projects/{$project->id}")
            ->assertSeeText('FREE-1')
            ->assertDontSeeText('Propose match');
    }

    public function test_lists_filter_by_match_status(): void
    {
        $matched = ProjectMatch::factory()->matched()->create();
        $matched->amisContract->forceFill(['contract_number' => 'MATCHED-1'])->save();
        AmisContract::factory()->create(['contract_number' => 'FREE-2']);

        $response = $this->actingAs(User::factory()->create())->get('/amis-contracts?match=unmatched');

        $response->assertOk();
        $response->assertSeeText('FREE-2');
        $response->assertDontSeeText('MATCHED-1');
        $this->actingAs(User::factory()->create())->get('/amis-contracts?match=matched')->assertSeeText(['MATCHED-1', 'Matched #'.$matched->id]);
    }

    private function contract(string $number, string $partner): AmisContract
    {
        $contract = AmisContract::factory()->create(['contract_number' => $number, 'partner_names' => [$partner]]);
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => 16, 'district_id' => 1601]);

        return $contract;
    }
}
