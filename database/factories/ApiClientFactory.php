<?php

namespace Database\Factories;

use App\Models\ApiClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiClient>
 */
class ApiClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['AMIS', 'LCIS']),
            'token_hash' => ApiClient::hashToken(ApiClient::generateToken()),
            'is_active' => true,
        ];
    }

    public function withToken(string $plainToken): static
    {
        return $this->state(fn (array $attributes): array => ['token_hash' => ApiClient::hashToken($plainToken)]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
