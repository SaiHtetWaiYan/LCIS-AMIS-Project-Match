<?php

namespace App\Models;

use Database\Factories\AmisContractFactory;
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
}
