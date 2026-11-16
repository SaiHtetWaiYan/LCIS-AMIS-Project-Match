<?php

namespace Tests\Feature\Services\Matching;

use App\Enums\SyncSource;
use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\LcisProject;
use App\Models\LcisProjectLocation;
use App\Models\User;
use App\Services\Matches\MatchWorkflow;
use App\Services\Matching\BestScoreRecorder;
use App\Services\Matching\MatchTermIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BestScoreRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_each_records_top_suggestion_without_touching_updated_at(): void
    {
        [$project, $contract] = $this->strongPair();
        $this->travel(1)->day();

        $this->assertSame(['lcis' => 1, 'amis' => 1], app(BestScoreRecorder::class)->refreshAll());

        $project->refresh();
        $this->assertSame(1.0, $project->best_score);
        $this->assertSame($contract->id, $project->best_counterpart_id);
        $this->assertFalse($project->best_is_pair);
        $this->assertNotNull($project->best_scored_at);
        $this->assertTrue($project->updated_at->isYesterday());

        $contract->refresh();
        $this->assertSame(1.0, $contract->best_score);
        $this->assertSame($project->id, $contract->best_counterpart_id);
    }

    public function test_proposing_stores_the_pair_and_rescores_records_that_had_the_paired_contract_as_top_suggestion(): void
    {
        [$project, $contract] = $this->strongPair();
        $rival = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ', 'activity_name' => 'ປູກກາເຟ']);
        $this->index();
        app(BestScoreRecorder::class)->refreshAll();
        $this->assertSame($contract->id, $rival->refresh()->best_counterpart_id);

        $match = app(MatchWorkflow::class)->propose(User::factory()->lcis()->create(), $project, $contract);

        $this->assertTrue($project->refresh()->best_is_pair);
        $this->assertSame($contract->id, $project->best_counterpart_id);
        $this->assertTrue($contract->refresh()->best_is_pair);
        $this->assertSame($project->id, $contract->best_counterpart_id);
        $this->assertNull($rival->refresh()->best_counterpart_id);
        $this->assertNull($rival->best_score);

        app(MatchWorkflow::class)->withdraw($match->proposer, $match);

        $this->assertFalse($project->refresh()->best_is_pair);
        $this->assertFalse($contract->refresh()->best_is_pair);
    }

    public function test_command_recalculates_every_record(): void
    {
        [$project] = $this->strongPair();

        $this->artisan('matching:score')
            ->expectsOutputToContain('1 projects')
            ->expectsOutputToContain('1 contracts')
            ->assertSuccessful();

        $this->assertSame(1.0, $project->refresh()->best_score);
    }

    /**
     * @return array{LcisProject, AmisContract}
     */
    private function strongPair(): array
    {
        $project = LcisProject::factory()->create(['company_name' => 'ບໍລິສັດ ສຳພັນໄຊ ຈຳກັດ', 'activity_name' => 'ປູກກາເຟ']);
        LcisProjectLocation::factory()->for($project, 'project')->create(['province_id' => 16, 'district_id' => 1601]);
        $contract = AmisContract::factory()->create(['partner_names' => ['ບໍລິສັດສຳພັນໄຊ'], 'contract_name' => 'ສັນຍາ ປູກກາເຟ']);
        AmisContractItem::factory()->for($contract, 'contract')->create(['province_id' => 16, 'district_id' => 1601, 'land_use_detail' => null]);
        $this->index();

        return [$project, $contract];
    }

    private function index(): void
    {
        app(MatchTermIndex::class)->rebuild(SyncSource::Lcis);
        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);
    }
}
