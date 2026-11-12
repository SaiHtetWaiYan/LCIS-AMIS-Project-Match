<?php

namespace Tests\Feature\Services\Matches;

use App\Enums\MatchAction;
use App\Enums\MatchStatus;
use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\MatchEvent;
use App\Models\ProjectMatch;
use App\Models\User;
use App\Services\Matches\MatchWorkflow;
use App\Services\Matches\MatchWorkflowException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class MatchWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_proposing_counts_as_the_proposers_side_confirming(): void
    {
        $this->freezeTime();
        $lcisUser = User::factory()->lcis()->create();
        $project = LcisProject::factory()->create();
        $contract = AmisContract::factory()->create();

        $match = $this->workflow()->propose($lcisUser, $project, $contract);

        $this->assertSame(MatchStatus::Proposed, $match->status);
        $this->assertSame($lcisUser->id, $match->proposed_by);
        $this->assertSame($lcisUser->id, $match->lcis_confirmed_by);
        $this->assertSame(now()->toDateTimeString(), $match->lcis_confirmed_at->toDateTimeString());
        $this->assertNull($match->amis_confirmed_at);
        $this->assertEvent($match, MatchAction::Proposed, $lcisUser, null, MatchStatus::Proposed);
    }

    public function test_an_amis_user_can_propose_too(): void
    {
        $amisUser = User::factory()->amis()->create();

        $match = $this->workflow()->propose($amisUser, LcisProject::factory()->create(), AmisContract::factory()->create());

        $this->assertSame($amisUser->id, $match->amis_confirmed_by);
        $this->assertNull($match->lcis_confirmed_at);
    }

    public function test_the_other_side_confirms_and_the_record_versions_are_kept(): void
    {
        $match = ProjectMatch::factory()->create();
        $amisUser = User::factory()->amis()->create();

        $match = $this->workflow()->confirm($amisUser, $match);

        $this->assertSame(MatchStatus::Matched, $match->status);
        $this->assertSame($amisUser->id, $match->amis_confirmed_by);
        $this->assertNotNull($match->matched_at);
        $this->assertSame($match->lcisProject->key_fields_hash, $match->lcis_hash_at_match);
        $this->assertSame($match->amisContract->key_fields_hash, $match->amis_hash_at_match);
        $this->assertEvent($match, MatchAction::Confirmed, $amisUser, MatchStatus::Proposed, MatchStatus::Matched);
    }

    public function test_the_other_side_rejects_with_a_reason(): void
    {
        $match = ProjectMatch::factory()->create();
        $amisUser = User::factory()->amis()->create();

        $match = $this->workflow()->reject($amisUser, $match, 'Different company and district.');

        $this->assertSame(MatchStatus::Rejected, $match->status);
        $this->assertSame('Different company and district.', $match->rejection_reason);
        $this->assertSame($amisUser->id, $match->rejected_by);
        $this->assertEvent($match, MatchAction::Rejected, $amisUser, MatchStatus::Proposed, MatchStatus::Rejected, 'Different company and district.');
    }

    public function test_the_proposer_withdraws_their_proposal(): void
    {
        $lcisUser = User::factory()->lcis()->create();
        $match = ProjectMatch::factory()->proposedBy($lcisUser)->create();

        $match = $this->workflow()->withdraw($lcisUser, $match);

        $this->assertSame(MatchStatus::Withdrawn, $match->status);
        $this->assertEvent($match, MatchAction::Withdrawn, $lcisUser, MatchStatus::Proposed, MatchStatus::Withdrawn);
    }

    public function test_an_admin_unmatches_and_both_records_become_available_again(): void
    {
        $match = ProjectMatch::factory()->matched()->create();
        $admin = User::factory()->admin()->create();

        $match = $this->workflow()->unmatch($admin, $match, 'Wrong contract.');
        $again = $this->workflow()->propose(User::factory()->amis()->create(), $match->lcisProject, $match->amisContract);

        $this->assertSame(MatchStatus::Unmatched, $match->status);
        $this->assertSame('Wrong contract.', $match->unmatch_reason);
        $this->assertEvent($match, MatchAction::Unmatched, $admin, MatchStatus::Matched, MatchStatus::Unmatched, 'Wrong contract.');
        $this->assertSame(MatchStatus::Proposed, $again->status);
    }

    public function test_a_rejected_pair_can_be_proposed_again(): void
    {
        $rejected = ProjectMatch::factory()->rejected()->create();

        $again = $this->workflow()->propose(User::factory()->lcis()->create(), $rejected->lcisProject, $rejected->amisContract);

        $this->assertNotSame($rejected->id, $again->id);
        $this->assertSame(MatchStatus::Proposed, $again->status);
    }

    public function test_an_admin_marks_a_flagged_match_reviewed_and_the_current_versions_become_the_baseline(): void
    {
        $match = ProjectMatch::factory()->matched()->needsReview()->create();
        $match->amisContract->forceFill(['key_fields_hash' => str_repeat('b', 64)])->save();

        $match = $this->workflow()->markReviewed(User::factory()->admin()->create(), $match, 'Name corrected in AMIS; still the same contract.');

        $this->assertFalse($match->needs_review);
        $this->assertNull($match->review_reasons);
        $this->assertSame(str_repeat('b', 64), $match->amis_hash_at_match);
        $this->assertSame(MatchStatus::Matched, $match->status);
    }

    public function test_forbids_confirming_from_the_proposers_own_side(): void
    {
        $match = ProjectMatch::factory()->create();

        $this->assertRefused('Your side has already confirmed this match', fn () => $this->workflow()->confirm(User::factory()->lcis()->create(), $match));
        $this->assertRefused('Your side has already confirmed this match', fn () => $this->workflow()->confirm($match->proposer, $match));
        $this->assertSame(MatchStatus::Proposed, $match->refresh()->status);
    }

    public function test_forbids_rejecting_from_the_proposers_own_side(): void
    {
        $match = ProjectMatch::factory()->create();

        $this->assertRefused('Your side has already confirmed this match', fn () => $this->workflow()->reject(User::factory()->lcis()->create(), $match, 'No.'));
    }

    public function test_forbids_proposing_a_project_or_contract_that_is_already_in_an_active_pair(): void
    {
        $existing = ProjectMatch::factory()->create();
        $user = User::factory()->amis()->create();

        $this->assertRefused("This LCIS project is already in match #{$existing->id} (Proposed).", fn () => $this->workflow()->propose($user, $existing->lcisProject, AmisContract::factory()->create()));
        $this->assertRefused("This AMIS contract is already in match #{$existing->id} (Proposed).", fn () => $this->workflow()->propose($user, LcisProject::factory()->create(), $existing->amisContract));
        $this->assertSame(1, ProjectMatch::count());
    }

    public function test_forbids_confirming_a_rejected_or_already_matched_pair(): void
    {
        $rejected = ProjectMatch::factory()->rejected()->create();
        $matched = ProjectMatch::factory()->matched()->create();
        $amisUser = User::factory()->amis()->create();

        $this->assertRefused('This match is Rejected', fn () => $this->workflow()->confirm($amisUser, $rejected));
        $this->assertRefused('This match is Matched', fn () => $this->workflow()->confirm($amisUser, $matched));
    }

    public function test_forbids_unmatching_by_a_non_admin_or_unmatching_a_proposal(): void
    {
        $matched = ProjectMatch::factory()->matched()->create();
        $proposed = ProjectMatch::factory()->create();

        $this->assertRefused('Only an admin can do this.', fn () => $this->workflow()->unmatch(User::factory()->lcis()->create(), $matched, 'Wrong.'));
        $this->assertRefused('Only a confirmed match can be unmatched; this one is Proposed.', fn () => $this->workflow()->unmatch(User::factory()->admin()->create(), $proposed, 'Wrong.'));
        $this->assertSame(MatchStatus::Matched, $matched->refresh()->status);
    }

    public function test_forbids_withdrawing_someone_elses_proposal(): void
    {
        $match = ProjectMatch::factory()->create();

        $this->assertRefused('Only the user who proposed this match can withdraw it.', fn () => $this->workflow()->withdraw(User::factory()->lcis()->create(), $match));
        $this->assertSame(MatchStatus::Proposed, $match->refresh()->status);
    }

    public function test_forbids_admins_and_deactivated_users_from_proposing_or_confirming(): void
    {
        $match = ProjectMatch::factory()->create();
        $project = LcisProject::factory()->create();
        $contract = AmisContract::factory()->create();

        $this->assertRefused('Only active LCIS or AMIS users', fn () => $this->workflow()->propose(User::factory()->admin()->create(), $project, $contract));
        $this->assertRefused('Only active LCIS or AMIS users', fn () => $this->workflow()->confirm(User::factory()->admin()->create(), $match));
        $this->assertRefused('Only active LCIS or AMIS users', fn () => $this->workflow()->propose(User::factory()->amis()->deactivated()->create(), $project, $contract));
    }

    public function test_forbids_proposing_a_record_missing_from_its_source(): void
    {
        $this->assertRefused('This AMIS contract is missing from AMIS', fn () => $this->workflow()->propose(
            User::factory()->lcis()->create(),
            LcisProject::factory()->create(),
            AmisContract::factory()->missing()->create(),
        ));
    }

    public function test_match_events_cannot_be_changed_or_deleted(): void
    {
        $match = $this->workflow()->propose(User::factory()->lcis()->create(), LcisProject::factory()->create(), AmisContract::factory()->create());
        $event = $match->events()->sole();

        try {
            $event->forceFill(['reason' => 'tampered'])->save();
            $this->fail('Updating an event should throw.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $event->delete();
    }

    private function workflow(): MatchWorkflow
    {
        return app(MatchWorkflow::class);
    }

    private function assertRefused(string $message, callable $action): void
    {
        try {
            $action();
            $this->fail("Expected the workflow to refuse: {$message}");
        } catch (MatchWorkflowException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }

    private function assertEvent(ProjectMatch $match, MatchAction $action, User $user, ?MatchStatus $from, MatchStatus $to, ?string $reason = null): void
    {
        $event = MatchEvent::query()->where('project_match_id', $match->id)->where('action', $action)->sole();

        $this->assertSame($user->id, $event->user_id);
        $this->assertSame($user->side, $event->side);
        $this->assertSame($from, $event->from_status);
        $this->assertSame($to, $event->to_status);
        $this->assertSame($reason, $event->reason);
    }
}
