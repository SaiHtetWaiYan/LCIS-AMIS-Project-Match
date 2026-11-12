<?php

namespace Tests\Feature;

use App\Models\ProjectMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchQueueControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_only_proposals_waiting_for_the_users_side(): void
    {
        $fromLcis = ProjectMatch::factory()->proposedBy(User::factory()->lcis()->create())->create();
        $fromAmis = ProjectMatch::factory()->proposedBy(User::factory()->amis()->create())->create();
        $matched = ProjectMatch::factory()->matched()->create();

        $this->actingAs(User::factory()->amis()->create())->get('/queue')
            ->assertOk()
            ->assertSeeText(['My queue', $fromLcis->lcisProject->project_code, 'Review'])
            ->assertDontSeeText([$fromAmis->lcisProject->project_code, $matched->lcisProject->project_code]);

        $this->actingAs(User::factory()->lcis()->create())->get('/queue')
            ->assertSeeText($fromAmis->lcisProject->project_code)
            ->assertDontSeeText($fromLcis->lcisProject->project_code);
    }

    public function test_says_when_nothing_is_waiting(): void
    {
        $this->actingAs(User::factory()->lcis()->create())->get('/queue')->assertSeeText('Nothing is waiting for your side.');
    }

    public function test_an_admins_queue_holds_the_active_matches_flagged_for_review(): void
    {
        $flagged = ProjectMatch::factory()->matched()->needsReview()->create();
        $proposed = ProjectMatch::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->get('/queue')
            ->assertOk()
            ->assertSeeText(['Active matches flagged for review', $flagged->lcisProject->project_code])
            ->assertDontSeeText($proposed->lcisProject->project_code);
    }
}
