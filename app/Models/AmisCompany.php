<?php

namespace App\Models;

use Database\Factories\AmisCompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * An AMIS company linked to at least one rent-land contract, as synced from GET /companies.
 */
class AmisCompany extends Model
{
    /** @use HasFactory<AmisCompanyFactory> */
    use HasFactory;

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'raw' => 'array',
            'synced_at' => 'datetime',
            'missing_since' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<AmisContract, $this>
     */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(AmisContract::class, 'amis_company_contract')->withPivot('shareholding_pct');
    }
}
