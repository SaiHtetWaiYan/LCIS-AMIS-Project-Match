<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Matching\Rules\AreaRule;
use App\Services\Matching\Rules\LocationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_explains_the_workflow_in_order(): void
    {
        $response = $this->actingAs(User::factory()->lcis()->create())->get('/guide');

        $response->assertOk();
        $response->assertSeeTextInOrder(['User guide', 'What this system does', 'Who does what', 'Find and propose a match', 'Reading the score', 'Confirm or reject a proposal', 'Questions and tips']);
    }

    public function test_shows_the_configured_rule_weights_and_points(): void
    {
        config([
            'matching.rules' => [LocationRule::class => 0.6, AreaRule::class => 0.4],
            'matching.location' => ['province' => 0.4, 'district' => 0.4, 'village' => 0.2],
        ]);

        $response = $this->actingAs(User::factory()->amis()->create())->get('/guide');

        $response->assertSeeTextInOrder(['Project location', '60%', 'Same province 40%, plus same district 40%, plus similar village 20%', 'Area (ha)', '40%']);
        $response->assertDontSeeText('How similar the company names are');
    }

    public function test_explains_capped_scores_and_the_compared_text_with_the_configured_cap(): void
    {
        config(['matching.caps.total_at_most' => 0.15]);

        $response = $this->actingAs(User::factory()->lcis()->create())->get('/guide');

        $response->assertSeeTextInOrder(['capped at 15%', '· compared:', 'Example: likely match', '= 87%', 'Example: capped', '= 45%', 'capped at 15%']);
    }

    public function test_marks_the_users_own_role(): void
    {
        $response = $this->actingAs(User::factory()->amis()->create())->get('/guide');

        $response->assertSeeTextInOrder(['AMIS', 'You', 'Propose pairs. Confirm or reject pairs proposed by LCIS users.']);
    }

    public function test_is_linked_from_the_navigation(): void
    {
        $this->actingAs(User::factory()->lcis()->create())->get('/dashboard')->assertSee(route('guide'));
    }
}
