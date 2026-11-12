<?php

namespace Database\Factories;

use App\Models\AmisCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AmisCompany>
 */
class AmisCompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(1, 9_999_999),
            'name_lao' => 'ບໍລິສັດ '.fake()->company().' ຈຳກັດ',
            'name_eng' => fake()->company().' Co., Ltd',
            'company_type' => 'company',
            'tax_id' => fake()->numerify('############'),
            'province_id' => 1,
            'district_id' => 104,
            'district_name' => 'ສີສັດຕະນາກ',
            'village' => 'ພະໂພ',
            'is_active' => true,
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
