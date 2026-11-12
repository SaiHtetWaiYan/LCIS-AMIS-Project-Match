@props(['title' => null, 'subtitle' => null])

<section {{ $attributes->merge(['class' => 'relative flex flex-col w-full bg-white text-gray-700 shadow-md rounded-xl bg-clip-border']) }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-4 px-4 sm:px-6 pt-5">
            <div>
                @if ($title)
                    <h2 class="font-sans text-xl font-semibold leading-snug text-blue-gray-900">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-col gap-2 shrink-0 sm:flex-row sm:items-center">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="px-4 sm:px-6 py-5">
        {{ $slot }}
    </div>
</section>
