<?php

namespace App\Models;

use App\Support\LikePattern;
use Database\Factories\LcisProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            ->orWhereHas('company', fn (Builder $query) => $query->whereLike('name_eng', $pattern)));
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
}
