<?php

namespace App\Http\Resources;

use App\Models\ProjectMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A confirmed LCIS ↔ AMIS pair as served by GET /api/v1/matches.
 *
 * @mixin ProjectMatch
 */
class MatchResource extends JsonResource
{
    /**
     * @return array{id: int, lcis: array<string, mixed>, amis: array<string, mixed>, matched_at: ?string, needs_review: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lcis' => [
                'project_id' => $this->lcis_project_id,
                'project_code' => $this->lcisProject->project_code,
                'activity_name' => $this->lcisProject->activity_name,
                'company_name' => $this->lcisProject->company_name,
            ],
            'amis' => [
                'contract_id' => $this->amis_contract_id,
                'contract_number' => $this->amisContract->contract_number,
                'contract_type' => $this->amisContract->contract_type,
                'contract_name' => $this->amisContract->contract_name,
            ],
            'matched_at' => $this->matched_at?->format('Y-m-d H:i:s'),
            'needs_review' => $this->needs_review,
        ];
    }
}
