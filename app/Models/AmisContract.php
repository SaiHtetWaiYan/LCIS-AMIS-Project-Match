<?php

namespace App\Models;

use App\Support\LikePattern;
use Database\Factories\AmisContractFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An AMIS rent-land contract (concession or rental) as synced from GET /income/rent-land. Keyed by contact_id.
 */
class AmisContract extends Model
{
    /** @use HasFactory<AmisContractFactory> */
    use HasFactory;

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'partner_names' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'area_ha' => 'decimal:4',
            'source_updated_at' => 'datetime',
            'raw' => 'array',
            'synced_at' => 'datetime',
            'missing_since' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AmisContractItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(AmisContractItem::class);
    }

    /**
     * @return BelongsToMany<AmisCompany, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(AmisCompany::class, 'amis_company_contract')->withPivot('shareholding_pct');
    }

    /**
     * Contract number, name, a partner name, or a linked company's name or tax ID contains the term.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $pattern = LikePattern::contains($term);

        $query->where(fn (Builder $query) => $query
            ->whereLike('contract_number', $pattern)
            ->orWhereLike('contract_name', $pattern)
            ->orWhereRaw('partner_names::text ilike ?', [$pattern])
            ->orWhereHas('companies', fn (Builder $query) => $query
                ->whereLike('name_lao', $pattern)
                ->orWhereLike('name_eng', $pattern)
                ->orWhereLike('tax_id', $pattern)));
    }

    /**
     * Has a land plot in the province; a contract without plots falls back to its budget province.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inProvince(Builder $query, int $provinceId): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereHas('items', fn (Builder $query) => $query->where('province_id', $provinceId))
            ->orWhere(fn (Builder $query) => $query->doesntHave('items')->where('from_province_id', $provinceId)));
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
