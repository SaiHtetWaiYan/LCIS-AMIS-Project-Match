<?php

namespace Database\Factories;

use App\Enums\SyncSource;
use App\Enums\SyncStatus;
use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncRun>
 */
class SyncRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => SyncSource::Lcis,
            'status' => SyncStatus::Succeeded,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'expected_count' => 10,
            'fetched_count' => 10,
            'created_count' => 2,
            'updated_count' => 1,
            'unchanged_count' => 7,
            'missing_count' => 0,
            'restored_count' => 0,
        ];
    }

    public function amis(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => SyncSource::Amis,
        ]);
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SyncStatus::Running,
            'finished_at' => null,
        ]);
    }

    public function failed(string $errorCode = 'CONNECTION_FAILED', string $message = 'Could not connect.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SyncStatus::Failed,
            'error_code' => $errorCode,
            'error_message' => $message,
        ]);
    }
}
