<?php

namespace Database\Factories;

use App\Enums\MatchStatus;
use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default: proposed by an LCIS user (so the LCIS side has confirmed), waiting for AMIS.
 *
 * @extends Factory<ProjectMatch>
 */
class ProjectMatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $proposer = User::factory()->lcis();

        return [
            'lcis_project_id' => LcisProject::factory(),
            'amis_contract_id' => AmisContract::factory(),
            'status' => MatchStatus::Proposed,
            'proposed_by' => $proposer,
            'lcis_confirmed_by' => fn (array $attributes) => $attributes['proposed_by'],
            'lcis_confirmed_at' => now(),
            'needs_review' => false,
        ];
    }

    public function proposedBy(User $user): static
    {
        $side = $user->side->value;

        return $this->state(fn (array $attributes): array => [
            'proposed_by' => $user->id,
            'lcis_confirmed_by' => $side === 'lcis' ? $user->id : null,
            'lcis_confirmed_at' => $side === 'lcis' ? now() : null,
            'amis_confirmed_by' => $side === 'amis' ? $user->id : null,
            'amis_confirmed_at' => $side === 'amis' ? now() : null,
        ]);
    }

    public function matched(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MatchStatus::Matched,
            'amis_confirmed_by' => User::factory()->amis(),
            'amis_confirmed_at' => now(),
            'matched_at' => now(),
            'lcis_hash_at_match' => fn (array $attributes) => LcisProject::find($attributes['lcis_project_id'])?->key_fields_hash,
            'amis_hash_at_match' => fn (array $attributes) => AmisContract::find($attributes['amis_contract_id'])?->key_fields_hash,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MatchStatus::Rejected,
            'rejected_by' => User::factory()->amis(),
            'rejected_at' => now(),
            'rejection_reason' => 'Different company.',
        ]);
    }

    public function needsReview(): static
    {
        return $this->state(fn (array $attributes): array => [
            'needs_review' => true,
            'review_reasons' => ['amis_changed'],
        ]);
    }
}
