<?php

namespace App\Services\Matches;

use App\Enums\MatchAction;
use App\Enums\MatchStatus;
use App\Enums\UserSide;
use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\MatchEvent;
use App\Models\ProjectMatch;
use App\Models\User;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Every change to a match goes through here.
 *
 *   propose   LCIS or AMIS user           → Proposed (counts as the proposer's side confirming)
 *   confirm   user of the other side      Proposed → Matched
 *   reject    user of the other side      Proposed → Rejected (reason required)
 *   withdraw  the proposer                Proposed → Withdrawn
 *   unmatch   admin                       Matched → Unmatched (reason required)
 *   review    admin                       clears needs_review on an active match
 *
 * The *Refusal() methods say why an action is not allowed (or null) and back both the policy
 * and the actions, which re-check them inside a transaction holding row locks.
 */
class MatchWorkflow
{
    public function __construct(private readonly MatchCardinality $cardinality) {}

    public function propose(User $user, LcisProject $project, AmisContract $contract): ProjectMatch
    {
        try {
            return DB::transaction(function () use ($user, $project, $contract): ProjectMatch {
                // Lock both records (always LCIS first) so competing proposals queue up here.
                $project = LcisProject::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
                $contract = AmisContract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
                $this->refuseIf($this->proposalRefusal($user, $project, $contract));

                $match = new ProjectMatch;
                $match->forceFill([
                    'lcis_project_id' => $project->id,
                    'amis_contract_id' => $contract->id,
                    'status' => MatchStatus::Proposed,
                    'proposed_by' => $user->id,
                    ...$this->confirmationBy($user),
                ])->save();

                $this->log($match, $user, MatchAction::Proposed, null);

                return $match;
            });
        } catch (UniqueConstraintViolationException) {
            throw new MatchWorkflowException(__('matches.errors.taken_meanwhile'));
        }
    }

    public function confirm(User $user, ProjectMatch $match): ProjectMatch
    {
        return $this->transition($match, fn (ProjectMatch $match): ?string => $this->confirmRefusal($user, $match), function (ProjectMatch $match) use ($user): void {
            $match->forceFill([
                ...$this->confirmationBy($user),
                'status' => MatchStatus::Matched,
                'matched_at' => now(),
                'lcis_hash_at_match' => $match->lcisProject()->value('key_fields_hash'),
                'amis_hash_at_match' => $match->amisContract()->value('key_fields_hash'),
            ])->save();

            $this->log($match, $user, MatchAction::Confirmed, MatchStatus::Proposed);
        });
    }

    public function reject(User $user, ProjectMatch $match, string $reason): ProjectMatch
    {
        return $this->transition($match, fn (ProjectMatch $match): ?string => $this->rejectRefusal($user, $match), function (ProjectMatch $match) use ($user, $reason): void {
            $match->forceFill([
                'status' => MatchStatus::Rejected,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'needs_review' => false,
            ])->save();

            $this->log($match, $user, MatchAction::Rejected, MatchStatus::Proposed, $reason);
        });
    }

    public function withdraw(User $user, ProjectMatch $match): ProjectMatch
    {
        return $this->transition($match, fn (ProjectMatch $match): ?string => $this->withdrawRefusal($user, $match), function (ProjectMatch $match) use ($user): void {
            $match->forceFill([
                'status' => MatchStatus::Withdrawn,
                'withdrawn_by' => $user->id,
                'withdrawn_at' => now(),
                'needs_review' => false,
            ])->save();

            $this->log($match, $user, MatchAction::Withdrawn, MatchStatus::Proposed);
        });
    }

    public function unmatch(User $user, ProjectMatch $match, string $reason): ProjectMatch
    {
        return $this->transition($match, fn (ProjectMatch $match): ?string => $this->unmatchRefusal($user, $match), function (ProjectMatch $match) use ($user, $reason): void {
            $match->forceFill([
                'status' => MatchStatus::Unmatched,
                'unmatched_by' => $user->id,
                'unmatched_at' => now(),
                'unmatch_reason' => $reason,
                'needs_review' => false,
            ])->save();

            $this->log($match, $user, MatchAction::Unmatched, MatchStatus::Matched, $reason);
        });
    }

    /**
     * An admin has checked a flagged match and keeps it; the current record versions become the baseline.
     */
    public function markReviewed(User $user, ProjectMatch $match, ?string $note = null): ProjectMatch
    {
        return $this->transition($match, fn (ProjectMatch $match): ?string => $this->reviewRefusal($user, $match), function (ProjectMatch $match) use ($user, $note): void {
            $match->forceFill([
                'needs_review' => false,
                'review_reasons' => null,
                'lcis_hash_at_match' => $match->status === MatchStatus::Matched ? $match->lcisProject()->value('key_fields_hash') : null,
                'amis_hash_at_match' => $match->status === MatchStatus::Matched ? $match->amisContract()->value('key_fields_hash') : null,
            ])->save();

            $this->log($match, $user, MatchAction::Reviewed, $match->status, $note);
        });
    }

    public function proposalRefusal(User $user, LcisProject $project, AmisContract $contract): ?string
    {
        return match (true) {
            ! $this->isMatchingSide($user) => __('matches.errors.side_only'),
            $project->missing_since !== null => __('matches.errors.lcis_missing'),
            $contract->missing_since !== null => __('matches.errors.amis_missing'),
            default => $this->cardinality->conflict($project, $contract),
        };
    }

    public function confirmRefusal(User $user, ProjectMatch $match): ?string
    {
        return match (true) {
            $match->status !== MatchStatus::Proposed => __('matches.errors.not_proposed', ['status' => $match->status->label()]),
            ! $this->isMatchingSide($user) => __('matches.errors.side_only'),
            $match->isConfirmedBy($user->side) => __('matches.errors.own_side_confirmed'),
            default => null,
        };
    }

    public function rejectRefusal(User $user, ProjectMatch $match): ?string
    {
        return $this->confirmRefusal($user, $match);
    }

    public function withdrawRefusal(User $user, ProjectMatch $match): ?string
    {
        return match (true) {
            $match->status !== MatchStatus::Proposed => __('matches.errors.not_proposed', ['status' => $match->status->label()]),
            $match->proposed_by !== $user->id => __('matches.errors.not_proposer'),
            default => null,
        };
    }

    public function unmatchRefusal(User $user, ProjectMatch $match): ?string
    {
        return match (true) {
            ! $user->isAdmin() => __('matches.errors.admin_only'),
            $match->status !== MatchStatus::Matched => __('matches.errors.not_matched', ['status' => $match->status->label()]),
            default => null,
        };
    }

    public function reviewRefusal(User $user, ProjectMatch $match): ?string
    {
        return match (true) {
            ! $user->isAdmin() => __('matches.errors.admin_only'),
            ! $match->needs_review => __('matches.errors.not_flagged'),
            ! $match->status->isActive() => __('matches.errors.not_active'),
            default => null,
        };
    }

    /**
     * Runs a change on a freshly locked copy of the match after re-checking that it is allowed.
     *
     * @param  Closure(ProjectMatch): ?string  $refusal
     * @param  Closure(ProjectMatch): void  $change
     */
    private function transition(ProjectMatch $match, Closure $refusal, Closure $change): ProjectMatch
    {
        return DB::transaction(function () use ($match, $refusal, $change): ProjectMatch {
            $locked = ProjectMatch::query()->whereKey($match->id)->lockForUpdate()->firstOrFail();
            $this->refuseIf($refusal($locked));
            $change($locked);

            return $locked;
        });
    }

    private function refuseIf(?string $refusal): void
    {
        if ($refusal !== null) {
            throw new MatchWorkflowException($refusal);
        }
    }

    private function isMatchingSide(User $user): bool
    {
        return $user->isActive() && in_array($user->side, [UserSide::Lcis, UserSide::Amis], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function confirmationBy(User $user): array
    {
        $prefix = $user->side === UserSide::Lcis ? 'lcis' : 'amis';

        return ["{$prefix}_confirmed_by" => $user->id, "{$prefix}_confirmed_at" => now()];
    }

    private function log(ProjectMatch $match, ?User $user, MatchAction $action, ?MatchStatus $from, ?string $reason = null): void
    {
        $event = new MatchEvent;
        $event->forceFill([
            'project_match_id' => $match->id,
            'user_id' => $user?->id,
            'side' => $user?->side,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $match->status,
            'reason' => $reason,
            'created_at' => now(),
        ])->save();
    }
}
