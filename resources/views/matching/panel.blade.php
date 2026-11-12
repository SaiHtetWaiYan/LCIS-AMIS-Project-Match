{{--
    Suggestions and manual search of the other system.
    $otherSide: 'amis' when showing an LCIS project, 'lcis' when showing an AMIS contract.
--}}
<x-card :title="__('matching.candidates.'.$otherSide.'_title')" :subtitle="__('matching.candidates.subtitle', ['count' => config('matching.candidates.limit')])">
    @if ($candidates === [])
        <p class="text-sm text-gray-500">{{ __('matching.candidates.none') }}</p>
    @else
        <ol class="divide-y divide-blue-gray-50">
            @foreach ($candidates as $candidate)
                @include('matching.candidate', ['candidate' => $candidate])
            @endforeach
        </ol>
    @endif
</x-card>

<x-card :title="__('matching.search.'.$otherSide.'_title')" :subtitle="__('matching.search.hint')" id="search">
    <form method="GET" action="{{ url()->current() }}#search" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1">
            <x-input-label for="q" :value="__('app.filters.search')" />
            <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$searchTerm" :placeholder="__($otherSide.'.search_hint')" />
            <x-input-error class="mt-2" :messages="$errors->get('q')" />
        </div>
        <x-primary-button>{{ __('matching.search.button') }}</x-primary-button>
    </form>

    @if ($searchResults !== null)
        <h3 class="mt-6 text-sm font-semibold text-blue-gray-900">{{ __('matching.search.results', ['term' => $searchTerm]) }}</h3>
        @if ($searchResults === [])
            <p class="mt-2 text-sm text-gray-500">{{ __('matching.search.no_results', ['term' => $searchTerm]) }}</p>
        @else
            <ol class="mt-4 divide-y divide-blue-gray-50">
                @foreach ($searchResults as $candidate)
                    @include('matching.candidate', ['candidate' => $candidate])
                @endforeach
            </ol>
        @endif
    @endif
</x-card>
