@props(['record'])

{{-- Stored best score of an LCIS project or AMIS contract (BestScoreRecorder): its pair's, or its top suggestion's. --}}
@php
    $percent = $record->best_score === null ? null : (int) round($record->best_score * 100);
    $counterpart = $record->bestCounterpart;
    $reference = match (true) {
        $counterpart instanceof \App\Models\AmisContract => $counterpart->contract_number,
        $counterpart instanceof \App\Models\LcisProject => $counterpart->project_code,
        default => null,
    } ?? ($record->best_counterpart_id ? '#'.$record->best_counterpart_id : null);
    $tone = match (true) {
        $percent === null => 'text-gray-400',
        $percent >= 75 => 'text-green-700',
        $percent >= 50 => 'text-amber-700',
        default => 'text-blue-gray-900',
    };
@endphp

<div {{ $attributes }} @if ($record->best_scored_at) title="{{ __('matching.best_score_as_of', ['date' => $record->best_scored_at->toDateTimeString()]) }}" @endif>
    <span class="md:hidden text-xs text-gray-500">{{ __('matching.best_score') }}:</span>
    @if ($record->best_scored_at === null)
        <span class="text-xs text-gray-400">{{ __('matching.best_score_pending') }}</span>
    @else
        <span class="font-semibold tabular-nums {{ $tone }}">{{ $percent === null ? __('app.not_available') : $percent.'%' }}</span>
        @if ($reference)
            <div class="text-xs text-gray-500">
                {{ $record->best_is_pair ? __('matching.best_score_pair') : __('matching.best_score_suggestion') }}
                · <span class="font-mono">{{ $reference }}</span>
            </div>
        @endif
    @endif
</div>
