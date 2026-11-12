<?php

namespace Tests\Feature\Services\Matches;

use App\Enums\MatchAction;
use App\Models\MatchEvent;
use App\Models\ProjectMatch;
use App\Services\Matches\ReviewFlagger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewFlaggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_flags_a_match_whose_contract_went_missing_and_logs_it_as_the_system(): void
    {
        $match = ProjectMatch::factory()->matched()->create();
        $match->amisContract->forceFill(['missing_since' => now()])->save();

        $flagged = app(ReviewFlagger::class)->flag();

        $match->refresh();
        $this->assertSame(1, $flagged);
        $this->assertTrue($match->needs_review);
        $this->assertSame(['amis_missing'], $match->review_reasons);
        $event = MatchEvent::query()->where('action', MatchAction::FlaggedForReview)->sole();
        $this->assertNull($event->user_id);
        $this->assertSame('the AMIS contract is missing from AMIS', $event->reason);
    }

    public function test_flags_a_confirmed_match_when_key_fields_changed_after_confirmation(): void
    {
        $match = ProjectMatch::factory()->matched()->create();
        $match->lcisProject->forceFill(['key_fields_hash' => str_repeat('c', 64)])->save();

        app(ReviewFlagger::class)->flag();

        $this->assertSame(['lcis_changed'], $match->refresh()->review_reasons);
    }

    public function test_does_not_flag_a_proposal_for_changed_fields_or_an_unchanged_match(): void
    {
        $proposal = ProjectMatch::factory()->create();
        $proposal->lcisProject->forceFill(['key_fields_hash' => str_repeat('c', 64)])->save();
        $unchanged = ProjectMatch::factory()->matched()->create();

        $this->assertSame(0, app(ReviewFlagger::class)->flag());
        $this->assertFalse($proposal->refresh()->needs_review);
        $this->assertFalse($unchanged->refresh()->needs_review);
    }

    public function test_does_not_flag_the_same_reason_twice(): void
    {
        $match = ProjectMatch::factory()->matched()->create();
        $match->amisContract->forceFill(['missing_since' => now()])->save();
        app(ReviewFlagger::class)->flag();

        $flaggedAgain = app(ReviewFlagger::class)->flag();

        $this->assertSame(0, $flaggedAgain);
        $this->assertSame(1, MatchEvent::query()->where('action', MatchAction::FlaggedForReview)->count());
    }

    public function test_ignores_inactive_matches(): void
    {
        $rejected = ProjectMatch::factory()->rejected()->create();
        $rejected->amisContract->forceFill(['missing_since' => now()])->save();

        $this->assertSame(0, app(ReviewFlagger::class)->flag());
    }
}
