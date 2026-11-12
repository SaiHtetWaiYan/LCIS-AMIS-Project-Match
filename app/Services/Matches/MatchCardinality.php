<?php

namespace App\Services\Matches;

use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;

/**
 * The one place that decides how many active pairs a record may be in: currently one-to-one.
 * The database enforces the same with partial unique indexes on project_matches; to allow one
 * LCIS project → many AMIS contracts, drop project_matches_active_lcis_unique and stop checking
 * the project here.
 */
class MatchCardinality
{
    /**
     * Why the pair cannot be proposed because of other active pairs, or null when it can.
     */
    public function conflict(LcisProject $project, AmisContract $contract): ?string
    {
        $projectMatch = ProjectMatch::query()->active()->where('lcis_project_id', $project->id)->first();

        if ($projectMatch !== null) {
            return __('matches.errors.lcis_taken', ['match' => $projectMatch->id, 'status' => $projectMatch->status->label()]);
        }

        $contractMatch = ProjectMatch::query()->active()->where('amis_contract_id', $contract->id)->first();

        if ($contractMatch !== null) {
            return __('matches.errors.amis_taken', ['match' => $contractMatch->id, 'status' => $contractMatch->status->label()]);
        }

        return null;
    }
}
