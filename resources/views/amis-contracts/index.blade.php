<x-app-layout>
    <x-slot name="title">{{ __('amis.contracts') }}</x-slot>

    <x-card :title="__('app.filters.title')">
        <form method="GET" action="{{ route('amis-contracts.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <x-input-label for="search" :value="__('app.filters.search')" />
                <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="$filters['search']" :placeholder="__('amis.search_hint')" />
            </div>
            <div>
                <x-input-label for="province" :value="__('app.filters.province')" />
                <x-select-input id="province" name="province" class="mt-1 block w-full">
                    <option value="">{{ __('app.filters.all_provinces') }}</option>
                    @foreach ($provinces as $id => $name)
                        <option value="{{ $id }}" @selected($filters['province'] === $id)>{{ $name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="type" :value="__('amis.fields.contract_type')" />
                <x-select-input id="type" name="type" class="mt-1 block w-full">
                    <option value="">{{ __('amis.all_types') }}</option>
                    @foreach ($contractTypes as $type)
                        <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ __('amis.contract_types.'.$type) }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="status" :value="__('amis.fields.status')" />
                <x-select-input id="status" name="status" class="mt-1 block w-full">
                    <option value="">{{ __('amis.all_statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ __('amis.statuses.'.$status) }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="source" :value="__('app.filters.source')" />
                <x-select-input id="source" name="source" class="mt-1 block w-full">
                    <option value="">{{ __('app.filters.all_records') }}</option>
                    @foreach (['present', 'missing'] as $source)
                        <option value="{{ $source }}" @selected($filters['source'] === $source)>{{ __('app.filters.'.$source) }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div class="flex items-end justify-end gap-3 sm:col-span-2 lg:col-span-6">
                <a href="{{ route('amis-contracts.index') }}"><x-secondary-button>{{ __('app.filters.reset') }}</x-secondary-button></a>
                <x-primary-button>{{ __('app.filters.apply') }}</x-primary-button>
            </div>
        </form>
    </x-card>

    <x-card :title="__('amis.contracts')">
        @if ($contracts->isEmpty())
            <p class="p-4 text-center text-sm text-blue-gray-900">{{ __('amis.empty') }}</p>
        @else
            <x-data-table :headings="['#', __('amis.fields.contract_name'), __('amis.fields.contract_type'), __('amis.fields.location'), __('amis.fields.area'), '']">
                @foreach ($contracts as $contract)
                    @php($item = $contract->items->first())
                    @php($partyNames = $contract->companies->isNotEmpty() ? $contract->companies->pluck('name_lao')->filter()->all() : $contract->partner_names)
                    <tr class="block md:table-row px-4 py-4 md:p-0 border-b border-blue-gray-50 hover:bg-slate-50">
                        <td class="hidden md:table-cell px-6 py-4 align-top text-blue-gray-900">{{ $contracts->firstItem() + $loop->index }}</td>
                        <td class="block md:table-cell md:px-6 md:py-4 align-top">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs text-gray-500">{{ $contract->contract_number ?? '#'.$contract->id }}</span>
                                @if ($contract->status && $contract->status !== 'nomal')
                                    <x-badge tone="amber">{{ __('amis.statuses.'.$contract->status) }}</x-badge>
                                @endif
                                @if ($contract->isMissingFromSource())
                                    <x-badge tone="red">{{ __('app.filters.missing') }}</x-badge>
                                @endif
                            </div>
                            <a href="{{ route('amis-contracts.show', $contract) }}" class="mt-1 block font-medium text-blue-gray-900 hover:underline">{{ $contract->contract_name ?? __('app.not_available') }}</a>
                            <div class="mt-0.5 text-gray-500">{{ implode(', ', $partyNames) }}</div>
                        </td>
                        <td class="block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-top">
                            @if ($contract->contract_type)
                                <x-badge>{{ __('amis.contract_types.'.$contract->contract_type) }}</x-badge>
                            @endif
                        </td>
                        <td class="block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-top">
                            @if ($item)
                                <div>{{ $item->province_id ? ($provinces[$item->province_id] ?? __('app.province_code', ['code' => $item->province_id])) : __('app.not_available') }}</div>
                                <div class="text-gray-500">{{ $item->district_name }}@if ($contract->items->count() > 1) · {{ __('amis.items.count', ['count' => $contract->items->count()]) }}@endif</div>
                            @else
                                <span class="text-gray-400">{{ __('app.not_available') }}</span>
                            @endif
                        </td>
                        <td class="block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-top whitespace-nowrap"><x-hectares :value="$contract->area_ha" /></td>
                        <td class="block md:table-cell mt-3 md:mt-0 md:px-6 md:py-4 align-top md:text-right">
                            <x-link-button variant="view" :href="route('amis-contracts.show', $contract)">{{ __('app.view') }}</x-link-button>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif

        <x-pagination :paginator="$contracts" />
    </x-card>
</x-app-layout>
