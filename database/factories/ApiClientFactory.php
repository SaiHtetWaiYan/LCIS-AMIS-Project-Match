<?php

namespace Database\Factories;

use App\Enums\UserSide;
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
        $token = ApiClient::generateToken();

        return [
            'name' => fake()->randomElement(['AMIS', 'LCIS']),
            'side' => fake()->randomElement([UserSide::Lcis, UserSide::Amis]),
            'token_hash' => ApiClient::hashToken($token),
            'token' => $token,
            'is_active' => true,
        ];
    }

    public function withToken(string $plainToken): static
    {
        return $this->state(fn (array $attributes): array => ['token_hash' => ApiClient::hashToken($plainToken), 'token' => $plainToken]);
    }

    public function forSide(UserSide $side): static
    {
        return $this->state(fn (array $attributes): array => ['name' => strtoupper($side->value), 'side' => $side]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
