<x-app-layout>
    <x-slot name="title">{{ __('matches.matches') }}</x-slot>

    <x-card :title="__('app.filters.title')">
        <form method="GET" action="{{ route('matches.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <x-input-label for="search" :value="__('app.filters.search')" />
                <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="$filters['search']" :placeholder="__('matches.search_hint')" />
            </div>
            <div>
                <x-input-label for="status" :value="__('matches.fields.status')" />
                <x-select-input id="status" name="status" class="mt-1 block w-full">
                    <option value="">{{ __('matches.any_status') }}</option>
                    @foreach (App\Enums\MatchStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status)>{{ $status->label() }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div class="flex items-end">
                <label for="review" class="inline-flex items-center gap-2 pb-2 text-sm text-blue-gray-900">
                    <input id="review" name="review" type="checkbox" value="1" @checked($filters['needs_review']) class="rounded border-gray-300 text-gray-700 shadow-sm focus:ring-indigo-500">
                    {{ __('matches.only_needs_review') }}
                </label>
            </div>
            <div class="flex items-end justify-end gap-3 sm:col-span-2 lg:col-span-4">
                <a href="{{ route('matches.index') }}"><x-secondary-button>{{ __('app.filters.reset') }}</x-secondary-button></a>
                <x-primary-button>{{ __('app.filters.apply') }}</x-primary-button>
            </div>
        </form>
    </x-card>

    <x-card :title="__('matches.matches')">
        <x-slot name="actions">
            <x-link-button variant="view" :href="route('matches.export', request()->query())">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                {{ __('matches.export') }}
            </x-link-button>
        </x-slot>

        @if ($matches->isEmpty())
            <p class="p-4 text-center text-sm text-blue-gray-900">{{ __('matches.empty') }}</p>
        @else
            <x-data-table :headings="['#', __('matches.pair'), __('matches.fields.status'), __('matches.updated'), '']">
                @foreach ($matches as $match)
                    <tr class="block md:table-row px-4 py-4 md:p-0 border-b border-blue-gray-50 hover:bg-slate-50">
                        <td class="block md:table-cell md:px-6 md:py-4 align-top font-mono text-xs text-gray-500">#{{ $match->id }}</td>
                        <td class="block md:table-cell mt-1 md:mt-0 md:px-6 md:py-4 align-top">@include('matches.partials.pair')</td>
                        <td class="block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-top">
                            <div class="flex flex-wrap gap-1">
                                <x-badge :tone="$match->status->tone()">{{ $match->status->label() }}</x-badge>
                                @if ($match->needs_review)
                                    <x-badge tone="amber">{{ __('matches.banner.needs_review') }}</x-badge>
                                @endif
                            </div>
                        </td>
                        <td class="block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-top whitespace-nowrap text-gray-500">
                            <time datetime="{{ $match->updated_at->toIso8601String() }}" title="{{ $match->updated_at->toDateTimeString() }}">{{ $match->updated_at->toDateString() }}</time>
                        </td>
                        <td class="block md:table-cell mt-3 md:mt-0 md:px-6 md:py-4 align-top md:text-right">
                            <x-link-button variant="view" :href="route('matches.show', $match)">{{ __('app.view') }}</x-link-button>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif

        <x-pagination :paginator="$matches" />
    </x-card>
</x-app-layout>
