@props(['card'])

@php
    $total = $card->total();
    $percent = $total === null ? null : (int) round($total * 100);
    $tone = match (true) {
        $percent === null => 'bg-gray-300',
        $percent >= 75 => 'bg-green-500',
        $percent >= 50 => 'bg-amber-500',
        default => 'bg-blue-gray-300',
    };
    $isWeak = $card->rulesWithData() <= 1;
    $cappedBy = collect($card->cappingOutcomes())->pluck('label');
@endphp

<div {{ $attributes->merge(['class' => 'w-full sm:w-48']) }}>
    <div class="flex items-baseline justify-between gap-2">
        <span class="text-xs text-gray-500">{{ __('matching.score') }}</span>
        <span class="text-2xl font-semibold tabular-nums text-blue-gray-900">{{ $percent === null ? __('app.not_available') : $percent.'%' }}</span>
    </div>
    <div class="mt-1 h-2 w-full rounded-full bg-blue-gray-50" role="presentation">
        <div class="h-2 rounded-full {{ $tone }}" style="width: {{ $percent ?? 0 }}%"></div>
    </div>
    <p @class(['mt-1 text-xs', 'text-amber-700 font-medium' => $isWeak, 'text-gray-500' => ! $isWeak])>
        {{ __('matching.rules_with_data', ['count' => $card->rulesWithData(), 'total' => $card->ruleCount()]) }}
        @if ($isWeak)
            — {{ __('matching.weak_basis') }}
        @endif
    </p>
    @if ($cappedBy->isNotEmpty())
        <p class="mt-1 text-xs font-medium text-amber-700">
            {{ __('matching.capped', ['percent' => $percent, 'rules' => $cappedBy->implode(', ')]) }}
        </p>
    @endif
</div>
