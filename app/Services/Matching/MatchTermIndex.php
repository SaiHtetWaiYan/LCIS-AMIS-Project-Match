<?php

namespace App\Services\Matching;

use App\Enums\SyncSource;
use App\Models\AmisContract;
use App\Models\LcisProject;
use Illuminate\Support\Facades\DB;

/**
 * Builds match_terms: every company name and activity text of a side, normalised, so that
 * pg_trgm can compare them. Rebuilt in full after each sync of that side.
 */
class MatchTermIndex
{
    public const KIND_NAME = 'name';

    public const KIND_ACTIVITY = 'activity';

    public function __construct(private readonly TextNormalizer $normalizer) {}

    public function rebuild(SyncSource $side): int
    {
        return DB::transaction(function () use ($side): int {
            DB::table('match_terms')->where('side', $side->value)->delete();
            $count = 0;

            $chunk = function (iterable $records) use ($side, &$count): void {
                $count += $this->insert($side, $records);
            };

            match ($side) {
                SyncSource::Lcis => LcisProject::query()
                    ->select(['id', 'company_id', 'company_name', 'activity_name'])
                    ->with('company:id,name,name_eng')
                    ->chunkById(500, $chunk),
                SyncSource::Amis => AmisContract::query()
                    ->select(['id', 'contract_name', 'partner_names'])
                    ->with(['items:id,amis_contract_id,land_use_detail', 'companies' => fn ($query) => $query->select(['amis_companies.id', 'name_lao', 'name_eng'])->whereNull('missing_since')])
                    ->chunkById(500, $chunk),
            };

            return $count;
        });
    }

    /**
     * Whether the database locale lets pg_trgm read Lao letters. Under a non-UTF-8 locale
     * (e.g. plain "C") every Lao similarity would silently be 0.
     */
    public function databaseSupportsLao(): bool
    {
        return (float) DB::scalar("select similarity('ສຳພັນໄຊ', 'ສຳພັນໄຊ')") === 1.0;
    }

    /**
     * The texts of one LCIS project, as [kind, source, text] rows.
     *
     * @return list<array{string, string, ?string}>
     */
    public function lcisTexts(LcisProject $project): array
    {
        return [
            [self::KIND_NAME, 'lcis_company', $project->company_name],
            [self::KIND_NAME, 'lcis_company', $project->company?->name],
            [self::KIND_NAME, 'lcis_company_eng', $project->company?->name_eng],
            [self::KIND_ACTIVITY, 'lcis_activity', $project->activity_name],
        ];
    }

    /**
     * The texts of one AMIS contract, as [kind, source, text] rows.
     *
     * @return list<array{string, string, ?string}>
     */
    public function amisTexts(AmisContract $contract): array
    {
        $texts = [[self::isCompanyName($contract->contract_name) ? self::KIND_NAME : self::KIND_ACTIVITY, 'amis_contract_name', $contract->contract_name]];

        foreach ($contract->companies as $company) {
            $texts[] = [self::KIND_NAME, 'amis_company_lao', $company->name_lao];
            $texts[] = [self::KIND_NAME, 'amis_company_eng', $company->name_eng];
        }

        foreach ($contract->partner_names as $partnerName) {
            $texts[] = [self::KIND_NAME, 'amis_partner', $partnerName];
        }

        foreach ($contract->items as $item) {
            $texts[] = [self::KIND_ACTIVITY, 'amis_land_use', $item->land_use_detail];
        }

        return $texts;
    }

    /**
     * Whether a text is a company name (ບໍລິສັດ…) rather than an activity. About a third of AMIS
     * contracts are named after the company; such a name is compared with company names, not
     * with LCIS activities.
     */
    public static function isCompanyName(?string $text): bool
    {
        $compact = (string) preg_replace('/[\s\x{200B}-\x{200D}\x{2060}\x{FEFF}]+/u', '', (string) $text);

        return preg_match('/^(ສະຫະ|ກຸ່ມ)?ບໍລ[ິີ]ສັດ/u', $compact) === 1;
    }

    /**
     * @param  iterable<LcisProject|AmisContract>  $records
     */
    private function insert(SyncSource $side, iterable $records): int
    {
        $rows = [];

        foreach ($records as $record) {
            $texts = $record instanceof LcisProject ? $this->lcisTexts($record) : $this->amisTexts($record);
            $seen = [];

            foreach ($texts as [$kind, $source, $text]) {
                $norm = $this->normalizer->normalize($text);
                $key = $kind.'|'.$norm;

                if ($norm === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $rows[] = ['side' => $side->value, 'record_id' => $record->id, 'kind' => $kind, 'source' => $source, 'text' => trim($text), 'text_norm' => $norm];
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('match_terms')->insert($chunk);
        }

        return count($rows);
    }
}
