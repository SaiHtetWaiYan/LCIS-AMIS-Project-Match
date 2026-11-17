<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListMatchesRequest;
use App\Http\Resources\MatchResource;
use App\Models\ProjectMatch;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/matches: confirmed LCIS ↔ AMIS pairs only, newest match first. A pair that is
 * unmatched later simply leaves this list, so callers should replace their copy on each fetch.
 */
class MatchController extends Controller
{
    public function __invoke(ListMatchesRequest $request): JsonResponse
    {
        $matches = ProjectMatch::query()
            ->with(['lcisProject', 'amisContract'])
            ->where('status', MatchStatus::Matched)
            ->when($request->validated('lcis_project_id'), fn ($query, $id) => $query->where('lcis_project_id', $id))
            ->when($request->validated('amis_contract_id'), fn ($query, $id) => $query->where('amis_contract_id', $id))
            ->orderByDesc('matched_at')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return response()->json([
            'data' => MatchResource::collection($matches->getCollection())->resolve($request),
            'meta' => [
                'total' => $matches->total(),
                'per_page' => $matches->perPage(),
                'current_page' => $matches->currentPage(),
                'last_page' => $matches->lastPage(),
            ],
        ]);
    }
}
