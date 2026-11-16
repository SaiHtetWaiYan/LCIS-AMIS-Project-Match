@props(['candidate', 'isPair' => false])

{{-- Compact score for list rows: the active pair's score, or the top suggestion's. --}}
@php
    $total = $candidate?->score->total();
    $percent = $total === null ? null : (int) round($total * 100);
    $reference = match (true) {
        $candidate?->record instanceof \App\Models\AmisContract => $candidate->record->contract_number,
        $candidate?->record instanceof \App\Models\LcisProject => $candidate->record->project_code,
        default => null,
    } ?? ($candidate ? '#'.$candidate->record->id : null);
    $tone = match (true) {
        $percent === null => 'text-gray-400',
        $percent >= 75 => 'text-green-700',
        $percent >= 50 => 'text-amber-700',
        default => 'text-blue-gray-900',
    };
@endphp

<div {{ $attributes }}>
    <span class="md:hidden text-xs text-gray-500">{{ __('matching.best_score') }}:</span>
    <span class="font-semibold tabular-nums {{ $tone }}">{{ $percent === null ? __('app.not_available') : $percent.'%' }}</span>
    @if ($candidate)
        <div class="text-xs text-gray-500">
            {{ $isPair ? __('matching.best_score_pair') : __('matching.best_score_suggestion') }}
            · <span class="font-mono">{{ $reference }}</span>
        </div>
    @endif
</div>
