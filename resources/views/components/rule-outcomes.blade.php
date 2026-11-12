@props(['card'])

<dl {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4']) }}>
    @foreach ($card->outcomes as $outcome)
        <div class="rounded-lg border border-blue-gray-50 bg-blue-gray-50/40 px-3 py-2">
            <dt class="flex items-center justify-between gap-2 text-xs font-medium text-gray-500">
                <span>{{ $outcome->label }} <span class="font-normal">· {{ (int) round($outcome->weight * 100) }}%</span></span>
                @if ($outcome->hasData())
                    <span class="tabular-nums text-sm font-semibold text-blue-gray-900">{{ (int) round($outcome->score * 100) }}%</span>
                @endif
            </dt>
            <dd class="mt-1 text-xs leading-relaxed text-blue-gray-900 break-words">
                {{ $outcome->hasData() ? $outcome->reason : __('matching.no_data') }}
            </dd>
        </div>
    @endforeach
</dl>
