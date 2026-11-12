<x-app-layout>
    <x-slot name="title">{{ __('matches.queue.title') }}</x-slot>

    <x-card :title="__('matches.queue.title')" :subtitle="$isAdmin ? __('matches.queue.admin_subtitle') : __('matches.queue.subtitle', ['side' => auth()->user()->side->label()])">
        @if ($matches->isEmpty())
            <p class="p-4 text-center text-sm text-blue-gray-900">{{ $isAdmin ? __('matches.queue.admin_empty') : __('matches.queue.empty') }}</p>
        @else
            <x-data-table :headings="['#', __('matches.pair'), __('matches.fields.proposed_by'), '']">
                @foreach ($matches as $match)
                    <tr class="block md:table-row px-4 py-4 md:p-0 border-b border-blue-gray-50 hover:bg-slate-50">
                        <td class="block md:table-cell md:px-6 md:py-4 align-top">
                            <span class="font-mono text-xs text-gray-500">#{{ $match->id }}</span>
                            @if ($match->needs_review)
                                <x-badge tone="amber" class="mt-1">{{ __('matches.banner.needs_review') }}</x-badge>
                            @endif
                        </td>
                        <td class="block md:table-cell mt-1 md:mt-0 md:px-6 md:py-4 align-top">@include('matches.partials.pair')</td>
                        <td class="block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-top text-gray-500">
                            {{ __('matches.proposed_ago', ['name' => $match->proposer?->name ?? __('app.not_available'), 'ago' => $match->created_at->diffForHumans()]) }}
                        </td>
                        <td class="block md:table-cell mt-3 md:mt-0 md:px-6 md:py-4 align-top md:text-right">
                            <x-link-button variant="edit" :href="route('matches.show', $match)">{{ __('matches.queue.open') }}</x-link-button>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif

        <x-pagination :paginator="$matches" />
    </x-card>
</x-app-layout>
