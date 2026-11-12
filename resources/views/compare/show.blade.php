{{--
    An LCIS project and an AMIS contract side by side. With $match it is that match's page
    (status, confirmations, actions, history); without, the pair can be proposed from here.
--}}
@php
    $project = $comparison->project;
    $contract = $comparison->contract;
    $title = $match ? __('matches.title', ['id' => $match->id]) : __('compare.title');
    $percent = fn (?float $score): ?int => $score === null ? null : (int) round($score * 100);
    $scoreTone = fn (?int $percent): string => match (true) {
        $percent === null => 'text-gray-400',
        $percent >= 75 => 'text-green-700',
        $percent >= 50 => 'text-amber-700',
        default => 'text-blue-gray-700',
    };
@endphp

<x-app-layout>
    <x-slot name="title">{{ $title }}</x-slot>
    <x-slot name="header">
        <div>
            <x-page-title>{{ $title }}</x-page-title>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                @if ($match)
                    <x-badge :tone="$match->status->tone()">{{ $match->status->label() }}</x-badge>
                    @if ($match->needs_review)
                        <x-badge tone="amber">{{ __('matches.banner.needs_review') }}</x-badge>
                    @endif
                @else
                    <span class="text-gray-500">{{ __('compare.subtitle') }}</span>
                @endif
            </div>
        </div>
    </x-slot>

    @if ($match?->needs_review)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
            {{ __('matches.review_hint', ['reasons' => collect($match->review_reasons)->map(fn ($reason) => __('matches.review_reasons.'.$reason))->implode('; ')]) }}
        </div>
    @endif

    {{-- The two records, then the score and each rule's values side by side. --}}
    <x-card :title="__('compare.matching_fields')" :subtitle="__('compare.matching_fields_hint')">
        <div class="-mx-4 sm:-mx-6 border-t border-blue-gray-50 text-sm">
            <div class="grid grid-cols-1 gap-4 px-4 py-4 sm:px-6 lg:grid-cols-[13rem_1fr_1fr] lg:gap-6">
                <div class="hidden lg:block">
                    <x-match-score :card="$comparison->score" class="sm:w-full" />
                </div>
                @foreach ([
                    ['record' => $project, 'tone' => 'blue', 'label' => __('lcis.project'), 'code' => $project->project_code, 'name' => $project->activity_name, 'url' => route('lcis-projects.show', $project), 'other' => $match ? null : $project->activeMatch, 'side' => 'lcis'],
                    ['record' => $contract, 'tone' => 'green', 'label' => __('amis.contract'), 'code' => $contract->contract_number, 'name' => $contract->contract_name, 'url' => route('amis-contracts.show', $contract), 'other' => $match ? null : $contract->activeMatch, 'side' => 'amis'],
                ] as $side)
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :tone="$side['tone']">{{ $side['label'] }}</x-badge>
                            <span class="font-mono text-xs text-gray-500">{{ $side['code'] ?? '#'.$side['record']->id }}</span>
                            @if ($side['record']->isMissingFromSource())
                                <x-badge tone="red">{{ __('app.missing_since', ['date' => $side['record']->missing_since->toDateString()]) }}</x-badge>
                            @endif
                        </div>
                        <a href="{{ $side['url'] }}" class="mt-1 block font-semibold text-blue-gray-900 hover:underline">{{ $side['name'] ?? $side['label'] }}</a>
                        @if ($side['other'])
                            <p class="mt-2 flex flex-wrap items-center gap-1 text-xs text-gray-600">
                                {{ __('compare.in_other_match.'.$side['side']) }} <x-match-badge :match="$side['other']" />
                            </p>
                        @endif
                    </div>
                @endforeach
                <div class="lg:hidden">
                    <x-match-score :card="$comparison->score" />
                </div>
            </div>

            @foreach ($comparison->score->outcomes as $outcome)
                @php($values = $comparison->valuesFor($outcome))
                @php($rulePercent = $percent($outcome->score))
                <div class="grid grid-cols-1 gap-x-6 gap-y-2 border-t border-blue-gray-50 px-4 py-4 sm:px-6 lg:grid-cols-[13rem_1fr_1fr]">
                    <div class="flex items-baseline justify-between gap-2 lg:block">
                        <div>
                            <p class="font-medium text-blue-gray-900">{{ $outcome->label }}</p>
                            <p class="text-xs text-gray-500">{{ __('compare.weight', ['percent' => (int) round($outcome->weight * 100)]) }}</p>
                        </div>
                        <p class="text-lg font-semibold tabular-nums {{ $scoreTone($rulePercent) }} lg:mt-1">{{ $rulePercent === null ? __('app.not_available') : $rulePercent.'%' }}</p>
                    </div>
                    @foreach (['lcis' => $values->lcis, 'amis' => $values->amis] as $sideKey => $sideValues)
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 lg:hidden">{{ __($sideKey === 'lcis' ? 'lcis.project' : 'amis.contract') }}</p>
                            @if ($sideValues === [])
                                <p class="text-gray-400">{{ __('app.not_available') }}</p>
                            @else
                                <ul class="space-y-1">
                                    @foreach ($sideValues as $value)
                                        <li class="break-words text-blue-gray-900">
                                            {{ $value['text'] }}
                                            @if ($value['note'])
                                                <span class="text-xs text-gray-500">· {{ $value['note'] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                    <p class="text-xs leading-relaxed text-gray-600 break-words lg:col-start-2 lg:col-span-2">
                        {{ $outcome->hasData() ? $outcome->reason : __('matching.no_data') }}
                    </p>
                </div>
            @endforeach
        </div>
    </x-card>

    <x-card :title="__('compare.other_fields')" :subtitle="__('compare.other_fields_hint')">
        <div class="-mx-4 sm:-mx-6 text-sm">
            <div class="hidden border-y border-blue-gray-100 bg-blue-gray-50/50 px-6 py-3 text-blue-gray-900/70 lg:grid lg:grid-cols-[13rem_1fr_1fr] lg:gap-6">
                <span>{{ __('compare.field') }}</span>
                <span>{{ __('lcis.project') }}</span>
                <span>{{ __('amis.contract') }}</span>
            </div>
            @foreach ($comparison->details as $detail)
                <div @class([
                    'grid grid-cols-1 gap-x-6 gap-y-1 border-b border-blue-gray-50 px-4 py-3 sm:px-6 lg:grid-cols-[13rem_1fr_1fr] last:border-0',
                    'bg-amber-50' => $detail->differs === true,
                ])>
                    <div class="flex items-center gap-2 font-medium text-blue-gray-900">
                        {{ $detail->label }}
                        @if ($detail->differs === true)
                            <x-badge tone="amber">{{ __('compare.differs') }}</x-badge>
                        @elseif ($detail->differs === false)
                            <x-badge tone="green">{{ __('compare.same') }}</x-badge>
                        @endif
                    </div>
                    @foreach (['lcis' => $detail->lcis, 'amis' => $detail->amis] as $sideKey => $value)
                        <div class="min-w-0 break-words">
                            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 lg:hidden">{{ __($sideKey === 'lcis' ? 'lcis.project' : 'amis.contract') }}:</span>
                            @if ($value === null)
                                <span class="text-gray-400">{{ __('app.not_available') }}</span>
                            @else
                                <span class="text-blue-gray-900">{{ $value }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </x-card>

    @if ($match)
        @include('matches.partials.match', ['match' => $match])
    @else
        @include('compare.partials.proposal')
    @endif

    @if ($earlierMatches->isNotEmpty())
        <x-card :title="__('compare.earlier.title')">
            <ul class="space-y-2 text-sm">
                @foreach ($earlierMatches as $earlier)
                    <li>
                        <a href="{{ route('matches.show', $earlier) }}" class="text-blue-gray-900 hover:underline">
                            {{ __('compare.earlier.item', ['id' => $earlier->id, 'status' => $earlier->status->label(), 'date' => $earlier->updated_at->toDateString()]) }}
                        </a>
                        @if ($earlier->rejection_reason || $earlier->unmatch_reason)
                            <span class="text-gray-500">— {{ $earlier->rejection_reason ?? $earlier->unmatch_reason }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
