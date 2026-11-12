<?php

namespace Tests\Feature\Services\Amis;

use App\Services\Amis\AreaUnitConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AreaUnitConverterTest extends TestCase
{
    /**
     * @return array<string, array{float, ?string, ?float}>
     */
    public static function conversions(): array
    {
        return [
            'hectare in Lao' => [12.5, 'ເຮັກຕາ', 12.5],
            'hectare abbreviation' => [3, 'ha', 3.0],
            'square metre in Lao' => [1300, 'ຕາແມັດ', 0.13],
            'square metre with a space and zero-width space' => [20000, " ຕາ\u{200B}ແມັດ ", 2.0],
            'square metre symbol' => [5000, 'm²', 0.5],
            'square kilometre in Lao' => [2, 'ກິໂລແມັດມົນທົນ', 200.0],
            'square kilometre abbreviation' => [1.5, 'KM2', 150.0],
            'unknown unit' => [10, 'ໄລ່', null],
            'no unit' => [10, null, null],
        ];
    }

    #[DataProvider('conversions')]
    public function test_converts_area_to_hectares(float $area, ?string $unit, ?float $expectedHectares): void
    {
        $hectares = app(AreaUnitConverter::class)->toHectares($area, $unit);

        if ($expectedHectares === null) {
            $this->assertNull($hectares);
        } else {
            $this->assertEqualsWithDelta($expectedHectares, $hectares, 0.000001);
        }
    }

    public function test_uses_units_added_to_the_config(): void
    {
        config(['matching.area_units.rai' => 0.16]);

        $this->assertEqualsWithDelta(1.6, app(AreaUnitConverter::class)->toHectares(10, 'Rai'), 0.000001);
    }
}
