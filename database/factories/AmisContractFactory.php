<?php

namespace Database\Factories;

use App\Models\AmisContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AmisContract>
 */
class AmisContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(1, 9_999_999),
            'contract_type' => 'concession',
            'business_type_id' => 1,
            'business_type_name' => 'ກະສິກຳ',
            'contract_number' => fake()->unique()->numerify('####/MTC'),
            'contract_name' => 'ສັນຍາ '.fake()->words(3, true),
            'partner_names' => ['ບໍລິສັດ '.fake()->company()],
            'status' => 'nomal',
            'starts_on' => '2024-01-01',
            'ends_on' => '2029-01-01',
            'from_province_id' => 1,
            'area_ha' => null,
            'key_fields_hash' => hash('sha256', fake()->uuid()),
            'source_updated_at' => now()->subDay(),
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
