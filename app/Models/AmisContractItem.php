<?php

namespace App\Models;

use Database\Factories\AmisContractItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A land plot of an AMIS contract. area_ha is null when the unit could not be converted to hectares.
 */
class AmisContractItem extends Model
{
    /** @use HasFactory<AmisContractItemFactory> */
    use HasFactory;

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'area' => 'decimal:4',
            'area_ha' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<AmisContract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(AmisContract::class, 'amis_contract_id');
    }
}
