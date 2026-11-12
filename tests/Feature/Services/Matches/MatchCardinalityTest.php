<?php

namespace Tests\Feature\Services\Matches;

use App\Enums\MatchStatus;
use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use App\Models\User;
use App\Services\Matches\MatchCardinality;
use App\Services\Matches\MatchWorkflow;
use App\Services\Matches\MatchWorkflowException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatchCardinalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_competing_proposal_that_passed_the_check_is_stopped_by_the_database_with_a_friendly_message(): void
    {
        // Simulates two users proposing at the same moment: both pass the application check.
        $this->app->instance(MatchCardinality::class, new class extends MatchCardinality
        {
            public function conflict(LcisProject $project, AmisContract $contract): ?string
            {
                return null;
            }
        });
        $project = LcisProject::factory()->create();
        app(MatchWorkflow::class)->propose(User::factory()->lcis()->create(), $project, AmisContract::factory()->create());

        try {
            app(MatchWorkflow::class)->propose(User::factory()->amis()->create(), $project, AmisContract::factory()->create());
            $this->fail('The second proposal should have been refused.');
        } catch (MatchWorkflowException $exception) {
            $this->assertSame('Someone else proposed a match for one of these records a moment ago. Reload the page to see it.', $exception->getMessage());
        }

        $this->assertSame(1, ProjectMatch::query()->active()->where('lcis_project_id', $project->id)->count());
        $this->assertSame(1, DB::table('match_events')->count());
    }

    public function test_the_database_allows_one_active_pair_per_amis_contract(): void
    {
        $contract = AmisContract::factory()->create();
        ProjectMatch::factory()->matched()->create(['amis_contract_id' => $contract->id]);

        $this->expectException(UniqueConstraintViolationException::class);

        ProjectMatch::factory()->create(['amis_contract_id' => $contract->id]);
    }

    public function test_the_database_keeps_inactive_pairs_out_of_the_one_to_one_rule(): void
    {
        $project = LcisProject::factory()->create();
        ProjectMatch::factory()->rejected()->create(['lcis_project_id' => $project->id]);
        ProjectMatch::factory()->create(['lcis_project_id' => $project->id, 'status' => MatchStatus::Withdrawn]);

        $active = ProjectMatch::factory()->create(['lcis_project_id' => $project->id]);

        $this->assertModelExists($active);
    }
}
