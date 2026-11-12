<?php

namespace Database\Factories;

use App\Models\LcisProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LcisProject>
 */
class LcisProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(1, 9_999_999),
            'project_code' => 'P-'.fake()->unique()->numerify('#####'),
            'activity_name' => 'ໂຄງການ '.fake()->words(3, true),
            'project_type_id' => 1,
            'project_type' => 'Agriculture',
            'status' => 'active',
            'company_id' => null,
            'company_name' => 'ບໍລິສັດ '.fake()->company().' ຈຳກັດ',
            'responsible_level' => 'province',
            'responsible_province_id' => 1,
            'responsible_province_name' => 'ນະຄອນຫຼວງວຽງຈັນ',
            'responsible_district_id' => 104,
            'responsible_district_name' => 'ສີສັດຕະນາກ',
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
