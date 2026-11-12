<?php

namespace App\Models;

use Database\Factories\LcisCompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A company as synced from LCIS GET /companies. Keyed by the LCIS id; raw holds the full payload.
 */
class LcisCompany extends Model
{
    /** @use HasFactory<LcisCompanyFactory> */
    use HasFactory;

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw' => 'array',
            'synced_at' => 'datetime',
            'missing_since' => 'datetime',
        ];
    }

    /**
     * @return HasMany<LcisProject, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(LcisProject::class, 'company_id');
    }
}
