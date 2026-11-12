<?php

namespace Database\Factories;

use App\Enums\MatchAction;
use App\Enums\MatchStatus;
use App\Enums\UserSide;
use App\Models\MatchEvent;
use App\Models\ProjectMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchEvent>
 */
class MatchEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_match_id' => ProjectMatch::factory(),
            'user_id' => User::factory()->lcis(),
            'side' => UserSide::Lcis,
            'action' => MatchAction::Proposed,
            'from_status' => null,
            'to_status' => MatchStatus::Proposed,
            'reason' => null,
            'created_at' => now(),
        ];
    }
}
