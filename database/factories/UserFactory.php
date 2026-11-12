<?php

namespace Database\Factories;

use App\Enums\UserSide;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'side' => UserSide::Lcis,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function lcis(): static
    {
        return $this->state(fn (array $attributes): array => [
            'side' => UserSide::Lcis,
        ]);
    }

    public function amis(): static
    {
        return $this->state(fn (array $attributes): array => [
            'side' => UserSide::Amis,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'side' => UserSide::Admin,
        ]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deactivated_at' => now(),
        ]);
    }
}
