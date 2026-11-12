<x-app-layout>
    <x-slot name="title">{{ __('users.title') }}</x-slot>

    <x-card :title="__('users.filters.title')">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-input-label for="search" :value="__('users.filters.search')" />
                <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="$filters['search']" />
            </div>
            <div>
                <x-input-label for="side-filter" :value="__('users.fields.side')" />
                <x-select-input id="side-filter" name="side" class="mt-1 block w-full">
                    <option value="">{{ __('users.filters.all_sides') }}</option>
                    @foreach (App\Enums\UserSide::options() as $value => $label)
                        <option value="{{ $value }}" @selected($filters['side'] === $value)>{{ $label }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="status-filter" :value="__('users.fields.status')" />
                <x-select-input id="status-filter" name="status" class="mt-1 block w-full">
                    <option value="">{{ __('users.filters.all_statuses') }}</option>
                    @foreach (['active', 'deactivated'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ __('users.statuses.'.$status) }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.users.index') }}"><x-secondary-button>{{ __('users.filters.reset') }}</x-secondary-button></a>
                <x-primary-button>{{ __('users.filters.apply') }}</x-primary-button>
            </div>
        </form>
    </x-card>

    <x-card :title="__('users.title')">
        @can('create', App\Models\User::class)
            <x-slot name="actions">
                <x-link-button :href="route('admin.users.create')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    {{ __('users.create') }}
                </x-link-button>
            </x-slot>
        @endcan

        @if ($users->isEmpty())
            <p class="p-4 text-center text-sm text-blue-gray-900">{{ __('users.empty') }}</p>
        @else
            <div class="-mx-4 sm:-mx-6 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="hidden md:table-header-group">
                        <tr>
                            @foreach (['#', __('users.fields.name'), __('users.fields.side'), __('users.fields.status'), ''] as $heading)
                                <th scope="col" class="px-6 py-4 border-y border-blue-gray-100 bg-blue-gray-50/50 font-normal text-blue-gray-900/70">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr class="block md:table-row px-4 py-4 md:p-0 border-b border-blue-gray-50 hover:bg-slate-50">
                                <td class="hidden md:table-cell px-6 py-4 align-middle text-blue-gray-900">{{ $users->firstItem() + $loop->index }}</td>
                                <td class="block md:table-cell md:px-6 md:py-4 align-middle">
                                    <div class="font-medium text-blue-gray-900">
                                        {{ $user->name }}
                                        @if ($user->is(auth()->user()))
                                            <span class="text-xs font-normal text-gray-500">({{ __('users.hints.you') }})</span>
                                        @endif
                                    </div>
                                    <div class="text-gray-500 break-all">{{ $user->email }}</div>
                                </td>
                                <td class="inline-block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-middle">
                                    <x-badge>{{ $user->side->label() }}</x-badge>
                                </td>
                                <td class="inline-block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-middle">
                                    @if ($user->isActive())
                                        <x-badge tone="green">{{ __('users.statuses.active') }}</x-badge>
                                    @else
                                        <x-badge tone="red">{{ __('users.statuses.deactivated') }}</x-badge>
                                    @endif
                                </td>
                                <td class="block md:table-cell mt-3 md:mt-0 md:px-6 md:py-4 align-middle whitespace-nowrap">
                                    <div class="flex flex-wrap md:justify-end items-center gap-2">
                                        @can('update', $user)
                                            <x-link-button variant="edit" :href="route('admin.users.edit', $user)">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931ZM16.862 4.487L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                                {{ __('users.actions.edit') }}
                                            </x-link-button>
                                        @endcan
                                        @can('deactivate', $user)
                                            <form method="POST" action="{{ route('admin.users.deactivation.store', $user) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center rounded-md bg-red-600 py-2 px-4 text-sm text-white shadow-md hover:bg-red-700">{{ __('users.actions.deactivate') }}</button>
                                            </form>
                                        @endcan
                                        @can('reactivate', $user)
                                            <form method="POST" action="{{ route('admin.users.deactivation.destroy', $user) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center rounded-md border border-green-600 bg-white py-2 px-4 text-sm text-green-700 shadow-sm hover:bg-green-50">{{ __('users.actions.reactivate') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-pagination :paginator="$users" />
    </x-card>
</x-app-layout>
