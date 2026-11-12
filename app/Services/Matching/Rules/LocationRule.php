<?php

namespace App\Services\Matching\Rules;

use App\Models\AmisContractItem;
use App\Models\LcisProjectLocation;
use App\Services\Matching\MatchPair;
use App\Services\Matching\MatchRule;
use App\Services\Matching\RuleResult;
use App\Services\Matching\RuleValues;
use App\Services\ProvinceDirectory;

/**
 * Best over every LCIS location × AMIS land plot: same province, plus same district, plus a
 * similar village (points in config/matching.php). District points need the province to match.
 *
 * Province and district codes are the national codes in both systems (checked by the
 * district code check on the sync page), so they are compared directly.
 */
class LocationRule implements MatchRule
{
    public function __construct(private readonly ProvinceDirectory $provinces) {}

    public function label(): string
    {
        return __('matching.rules.location');
    }

    public function evaluate(MatchPair $pair): ?RuleResult
    {
        $locations = $pair->project->locations->whereNotNull('province_id');
        $plots = $pair->contract->items->whereNotNull('province_id');

        if ($locations->isEmpty() || $plots->isEmpty()) {
            return null;
        }

        $best = null;

        foreach ($locations as $location) {
            foreach ($plots as $plot) {
                $candidate = $this->compare($location, $plot);

                if ($best === null || $candidate['score'] > $best['score']) {
                    $best = $candidate;
                }
            }
        }

        return new RuleResult($best['score'], $this->reason($best));
    }

    public function values(MatchPair $pair): RuleValues
    {
        return new RuleValues(
            RuleValues::list($pair->project->locations->map(fn (LcisProjectLocation $location): array => [
                $this->place($location->province_id, $location->district_name, $location->village_name),
                $location->is_fallback ? __('matching.value_notes.location_fallback') : null,
            ])),
            RuleValues::list($pair->contract->items->map(fn (AmisContractItem $plot): array => [
                $this->place($plot->province_id, $plot->district_name, $plot->village),
            ])),
        );
    }

    private function place(?int $provinceId, ?string $district, ?string $village): string
    {
        return collect([$this->provinces->name($provinceId), $district, $village])->filter(fn (?string $part): bool => trim((string) $part) !== '')->implode(' · ');
    }

    /**
     * @return array{score: float, province: bool, district: bool, village: bool, location: LcisProjectLocation, plot: AmisContractItem}
     */
    private function compare(LcisProjectLocation $location, AmisContractItem $plot): array
    {
        $sameProvince = $location->province_id === $plot->province_id;
        $sameDistrict = $sameProvince && $location->district_id !== null && $location->district_id === $plot->district_id;
        $similarVillage = $sameProvince && self::similarVillages($location->village_name, $plot->village);

        $points = config('matching.location');

        return [
            'score' => ($sameProvince ? $points['province'] : 0) + ($sameDistrict ? $points['district'] : 0) + ($similarVillage ? $points['village'] : 0),
            'province' => $sameProvince,
            'district' => $sameDistrict,
            'village' => $similarVillage,
            'location' => $location,
            'plot' => $plot,
        ];
    }

    /**
     * Equal, or one contains the other, after removing spaces and the ບ້ານ (village) prefix.
     */
    public static function similarVillages(?string $a, ?string $b): bool
    {
        $normalize = fn (?string $name): string => (string) preg_replace(['/[\s\x{200B}-\x{200D}\x{FEFF}]+/u', '/^(ບ້ານ|ban)/u'], '', mb_strtolower((string) $name));
        [$a, $b] = [$normalize($a), $normalize($b)];

        return $a !== '' && $b !== '' && (str_contains($a, $b) || str_contains($b, $a));
    }

    /**
     * @param  array{score: float, province: bool, district: bool, village: bool, location: LcisProjectLocation, plot: AmisContractItem}  $best
     */
    private function reason(array $best): string
    {
        $location = $best['location'];
        $plot = $best['plot'];
        $fallback = $location->is_fallback ? ' '.__('matching.reasons.location_fallback') : '';

        if (! $best['province']) {
            return __('matching.reasons.location_none', [
                'lcis' => $this->provinces->name($location->province_id),
                'amis' => $this->provinces->name($plot->province_id),
            ]).$fallback;
        }

        $parts = [__('matching.reasons.location_province', ['province' => $this->provinces->name($location->province_id)])];

        $parts[] = match (true) {
            $best['district'] => __('matching.reasons.location_district', ['district' => $plot->district_name ?? $plot->district_id]),
            $location->district_id === null || $plot->district_id === null => __('matching.reasons.location_district_unknown'),
            default => __('matching.reasons.location_other_district', ['lcis' => $location->district_name ?? $location->district_id, 'amis' => $plot->district_name ?? $plot->district_id]),
        };

        if ($best['village']) {
            $parts[] = __('matching.reasons.location_village', ['village' => $plot->village]);
        }

        return implode('; ', $parts).$fallback;
    }
}
