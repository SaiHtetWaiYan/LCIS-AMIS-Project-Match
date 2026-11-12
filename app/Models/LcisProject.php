<?php

namespace App\Models;

use Database\Factories\LcisProjectFactory;
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
}
