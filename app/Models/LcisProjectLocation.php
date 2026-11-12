<?php

namespace App\Models;

use Database\Factories\LcisProjectLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Where an LCIS project's land is. is_fallback marks rows built from the responsible province/district.
 */
class LcisProjectLocation extends Model
{
    /** @use HasFactory<LcisProjectLocationFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_fallback' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<LcisProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(LcisProject::class, 'lcis_project_id');
    }
}
