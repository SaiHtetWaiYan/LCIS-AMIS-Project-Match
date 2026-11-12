<?php

namespace App\Http\Controllers;

use App\Enums\MatchStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * How to use the app. Rule names, weights and points come from config/matching.php, so the
 * guide stays right when the scoring is tuned.
 */
class GuideController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('guide', [
            'side' => $request->user()->side,
            'rules' => collect(config('matching.rules'))
                ->map(fn (float $weight, string $ruleClass): array => [
                    'label' => app($ruleClass)->label(),
                    'weight' => (int) round($weight * 100),
                    'key' => class_basename($ruleClass),
                ])
                ->values(),
            'locationPoints' => array_map(fn (float $points): int => (int) round($points * 100), config('matching.location')),
            'areaFullWithin' => (int) round(config('matching.area.full_score_within') * 100),
            'areaZeroFrom' => (int) round(config('matching.area.zero_score_from') * 100),
            'candidateLimit' => (int) config('matching.candidates.limit'),
            'statuses' => MatchStatus::cases(),
        ]);
    }
}
