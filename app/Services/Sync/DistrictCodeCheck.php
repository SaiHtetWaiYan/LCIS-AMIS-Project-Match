<?php

namespace App\Services\Sync;

use App\Services\Matching\TextNormalizer;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Checks the assumption that LCIS and AMIS use the same national district codes, by comparing
 * the district names each system stores for a code. Codes found in only one system are ignored.
 */
class DistrictCodeCheck
{
    /**
     * Names at least this similar (after normalisation) are the same district spelled differently.
     */
    private const SAME_DISTRICT_SIMILARITY = 0.5;

    public function __construct(private readonly TextNormalizer $normalizer) {}

    /**
     * @return array{compared: int, spelling: list<array{code: int, lcis: list<string>, amis: list<string>}>, mismatches: list<array{code: int, lcis: list<string>, amis: list<string>}>}
     */
    public function run(): array
    {
        $lcisNames = $this->namesByCode(
            DB::table('lcis_projects')->select(['responsible_district_id as code', 'responsible_district_name as name'])->distinct()
                ->union(DB::table('lcis_project_locations')->select(['district_id as code', 'district_name as name'])->distinct()),
        );
        $amisNames = $this->namesByCode(
            DB::table('amis_contract_items')->select(['district_id as code', 'district_name as name'])->distinct()
                ->union(DB::table('amis_companies')->select(['district_id as code', 'district_name as name'])->distinct()),
        );

        $sharedCodes = array_intersect(array_keys($lcisNames), array_keys($amisNames));
        sort($sharedCodes);
        $spelling = [];
        $mismatches = [];

        foreach ($sharedCodes as $code) {
            $similarity = $this->bestSimilarity($lcisNames[$code], $amisNames[$code]);
            $entry = ['code' => $code, 'lcis' => $lcisNames[$code], 'amis' => $amisNames[$code]];

            if ($similarity >= 1.0) {
                continue;
            }

            if ($similarity >= self::SAME_DISTRICT_SIMILARITY) {
                $spelling[] = $entry;
            } else {
                $mismatches[] = $entry;
            }
        }

        return ['compared' => count($sharedCodes), 'spelling' => $spelling, 'mismatches' => $mismatches];
    }

    /**
     * Unifies Lao spelling like match texts do, and drops the district prefix (ເມືອງ, abbreviated ມ.).
     */
    public function normalize(string $name): string
    {
        $name = preg_replace('/^\s*(ເມືອງ|ມ\s*\.|district)\s*/u', '', mb_strtolower(trim($name))) ?? $name;

        return str_replace(' ', '', $this->normalizer->normalize($name));
    }

    /**
     * 1.0 when a normalised name is equal on both sides or one contains the other; otherwise the
     * best character-level similarity between any LCIS and AMIS name.
     *
     * @param  list<string>  $lcisNames
     * @param  list<string>  $amisNames
     */
    private function bestSimilarity(array $lcisNames, array $amisNames): float
    {
        $best = 0.0;

        foreach ($lcisNames as $lcisName) {
            foreach ($amisNames as $amisName) {
                [$a, $b] = [$this->normalize($lcisName), $this->normalize($amisName)];

                if ($a === '' || $b === '') {
                    continue;
                }

                if (str_contains($a, $b) || str_contains($b, $a)) {
                    return 1.0;
                }

                $best = max($best, self::characterSimilarity($a, $b));
            }
        }

        return $best;
    }

    /**
     * 1 − edit distance / longer length, counted in characters (not UTF-8 bytes, which would make
     * any two Lao words look alike).
     */
    private static function characterSimilarity(string $a, string $b): float
    {
        $charsA = mb_str_split($a);
        $charsB = mb_str_split($b);
        $previous = range(0, count($charsB));

        foreach ($charsA as $i => $charA) {
            $current = [$i + 1];

            foreach ($charsB as $j => $charB) {
                $current[$j + 1] = min($previous[$j + 1] + 1, $current[$j] + 1, $previous[$j] + ($charA === $charB ? 0 : 1));
            }

            $previous = $current;
        }

        return 1 - $previous[count($charsB)] / max(count($charsA), count($charsB));
    }

    /**
     * @return array<int, list<string>>
     */
    private function namesByCode(Builder $query): array
    {
        $names = [];

        foreach ($query->get() as $row) {
            if ($row->code !== null && $row->name !== null && trim($row->name) !== '') {
                $names[(int) $row->code][] = trim($row->name);
            }
        }

        return array_map(fn (array $list): array => array_values(array_unique($list)), $names);
    }
}
