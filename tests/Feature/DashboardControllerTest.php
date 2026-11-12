<?php

namespace Tests\Feature;

use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_unmatched_records_waiting_proposals_and_matches_by_status(): void
    {
        LcisProject::factory()->count(3)->create();
        LcisProject::factory()->missing()->create();
        AmisContract::factory()->count(2)->create();
        ProjectMatch::factory()->proposedBy(User::factory()->lcis()->create())->count(2)->create();
        ProjectMatch::factory()->matched()->needsReview()->create();
        ProjectMatch::factory()->rejected()->create();

        $response = $this->actingAs(User::factory()->amis()->create())->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('awaitingMe', 2);
        $response->assertViewHas('unmatchedLcis', 4);
        $response->assertViewHas('totalLcis', 7);
        $response->assertViewHas('unmatchedAmis', 3);
        $response->assertViewHas('needsReview', 1);
        $response->assertViewHas('statusCounts', fn ($counts): bool => $counts['proposed'] === 2 && $counts['matched'] === 1 && $counts['rejected'] === 1 && $counts['unmatched'] === 0);
        $response->assertSeeTextInOrder(['Waiting for your side', '2', 'LCIS projects not matched', '4', 'of 7 in the source']);
    }

    public function test_shows_the_last_sync_of_each_source_and_why_it_failed(): void
    {
        SyncRun::factory()->create();
        SyncRun::factory()->amis()->failed('API_KEY_EXPIRED', 'Key expired.')->create();

        $response = $this->actingAs(User::factory()->lcis()->create())->get('/dashboard');

        $response->assertSeeTextInOrder(['LCIS', 'Succeeded', 'AMIS', 'Failed', 'API_KEY_EXPIRED']);
    }

    public function test_admins_have_no_waiting_proposals_tile(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('awaitingMe', null);
        $response->assertDontSeeText('Waiting for your side');
    }
}
