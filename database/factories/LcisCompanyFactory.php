<?php

namespace Database\Factories;

use App\Models\LcisCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LcisCompany>
 */
class LcisCompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(1, 9_999_999),
            'name' => 'ບໍລິສັດ '.fake()->company().' ຈຳກັດ',
            'name_eng' => fake()->company().' Co., Ltd',
            'raw' => [],
            'payload_hash' => hash('sha256', fake()->uuid()),
            'synced_at' => now(),
            'missing_since' => null,
        ];
    }

    public function missing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'missing_since' => now(),
        ]);
    }
}
