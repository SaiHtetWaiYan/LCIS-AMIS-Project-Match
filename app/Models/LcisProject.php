<?php

namespace App\Models;

use App\Enums\MatchStatus;
use App\Services\Matching\TextNormalizer;
use App\Support\LikePattern;
use Database\Factories\LcisProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * An LCIS investment project as synced from GET /projects. Keyed by the LCIS id; raw holds the full payload.
 */
class LcisProject extends Model
{
    /** @use HasFactory<LcisProjectFactory> */
    use HasFactory;

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'area_ha' => 'decimal:4',
            'source_updated_at' => 'datetime',
            'raw' => 'array',
            'synced_at' => 'datetime',
            'missing_since' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<LcisCompany, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(LcisCompany::class, 'company_id');
    }

    /**
     * @return HasMany<LcisProjectLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(LcisProjectLocation::class);
    }

    /**
     * @return HasMany<ProjectMatch, $this>
     */
    public function matches(): HasMany
    {
        return $this->hasMany(ProjectMatch::class, 'lcis_project_id');
    }

    /**
     * The proposed or matched pair holding this record, if any.
     *
     * @return HasOne<ProjectMatch, $this>
     */
    public function activeMatch(): HasOne
    {
        return $this->hasOne(ProjectMatch::class, 'lcis_project_id')->whereIn('status', MatchStatus::active());
    }

    /**
     * unmatched: in no active pair; proposed / matched: in an active pair with that status.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withMatchStatus(Builder $query, string $status): void
    {
        $status === 'unmatched'
            ? $query->whereDoesntHave('activeMatch')
            : $query->whereHas('activeMatch', fn (Builder $query) => $query->where('status', $status));
    }

    /**
     * Code, activity, company name (Lao or English) contains the term.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $pattern = LikePattern::contains($term);

        $query->where(fn (Builder $query) => $query
            ->whereLike('project_code', $pattern)
            ->orWhereLike('activity_name', $pattern)
            ->orWhereLike('company_name', $pattern)
            ->orWhereHas('company', fn (Builder $query) => $query->whereLike('name_eng', $pattern))
            ->orWhereIn('id', self::idsWithNormalisedText($term)));
    }

    /**
     * Has land (or, until LCIS sends locations, its responsible office) in the province.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inProvince(Builder $query, int $provinceId): void
    {
        $query->whereHas('locations', fn (Builder $query) => $query->where('province_id', $provinceId));
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function presentInSource(Builder $query, bool $isPresent = true): void
    {
        $isPresent ? $query->whereNull('missing_since') : $query->whereNotNull('missing_since');
    }

    public function isMissingFromSource(): bool
    {
        return $this->missing_since !== null;
    }

    /**
     * Records whose normalised name or activity contains the normalised term, so searches ignore
     * zero-width spaces, Lao spelling variants and legal-form words.
     */
    private static function idsWithNormalisedText(string $term): QueryBuilder
    {
        $normalised = app(TextNormalizer::class)->normalize($term);

        return DB::table('match_terms')
            ->select('record_id')
            ->where('side', 'lcis')
            ->when($normalised === '', fn (QueryBuilder $query) => $query->whereRaw('false'))
            ->whereLike('text_norm', LikePattern::contains($normalised));
    }
}
