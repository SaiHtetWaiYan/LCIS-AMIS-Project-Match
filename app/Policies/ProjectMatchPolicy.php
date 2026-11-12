<?php

namespace App\Policies;

use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use App\Models\User;
use App\Services\Matches\MatchWorkflow;
use Illuminate\Auth\Access\Response;

/**
 * Who may do what with a match. The rules live in MatchWorkflow; denials carry its explanation.
 */
class ProjectMatchPolicy
{
    public function __construct(private readonly MatchWorkflow $workflow) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProjectMatch $match): bool
    {
        return true;
    }

    public function create(User $user, LcisProject $project, AmisContract $contract): Response
    {
        return $this->decide($this->workflow->proposalRefusal($user, $project, $contract));
    }

    public function confirm(User $user, ProjectMatch $match): Response
    {
        return $this->decide($this->workflow->confirmRefusal($user, $match));
    }

    public function reject(User $user, ProjectMatch $match): Response
    {
        return $this->decide($this->workflow->rejectRefusal($user, $match));
    }

    public function withdraw(User $user, ProjectMatch $match): Response
    {
        return $this->decide($this->workflow->withdrawRefusal($user, $match));
    }

    public function unmatch(User $user, ProjectMatch $match): Response
    {
        return $this->decide($this->workflow->unmatchRefusal($user, $match));
    }

    public function review(User $user, ProjectMatch $match): Response
    {
        return $this->decide($this->workflow->reviewRefusal($user, $match));
    }

    private function decide(?string $refusal): Response
    {
        return $refusal === null ? Response::allow() : Response::deny($refusal);
    }
}
