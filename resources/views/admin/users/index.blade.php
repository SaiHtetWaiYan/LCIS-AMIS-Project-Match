<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('users.title') }}
            </h2>
            @can('create', App\Models\User::class)
                <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    {{ __('users.create') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="px-4 py-3 rounded-md bg-green-50 dark:bg-green-900/30 text-sm text-green-800 dark:text-green-200" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" action="{{ route('admin.users.index') }}" class="px-4 sm:px-0 flex flex-col sm:flex-row gap-2">
                <label for="search" class="sr-only">{{ __('users.filters.search') }}</label>
                <x-text-input id="search" name="search" type="search" class="w-full sm:w-72" :value="$filters['search']" :placeholder="__('users.filters.search')" />

                <label for="side-filter" class="sr-only">{{ __('users.fields.side') }}</label>
                <x-select-input id="side-filter" name="side">
                    <option value="">{{ __('users.filters.all_sides') }}</option>
                    @foreach (App\Enums\UserSide::options() as $value => $label)
                        <option value="{{ $value }}" @selected($filters['side'] === $value)>{{ $label }}</option>
                    @endforeach
                </x-select-input>

                <label for="status-filter" class="sr-only">{{ __('users.fields.status') }}</label>
                <x-select-input id="status-filter" name="status">
                    <option value="">{{ __('users.filters.all_statuses') }}</option>
                    @foreach (['active', 'deactivated'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ __('users.statuses.'.$status) }}</option>
                    @endforeach
                </x-select-input>

                <div class="flex items-center gap-3">
                    <x-secondary-button type="submit">{{ __('users.filters.apply') }}</x-secondary-button>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">{{ __('users.filters.reset') }}</a>
                </div>
            </form>

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                @if ($users->isEmpty())
                    <p class="p-6 text-sm text-gray-500 dark:text-gray-400">{{ __('users.empty') }}</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="hidden md:table-header-group bg-gray-50 dark:bg-gray-900/50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th scope="col" class="px-6 py-3">{{ __('users.fields.name') }}</th>
                                <th scope="col" class="px-6 py-3">{{ __('users.fields.side') }}</th>
                                <th scope="col" class="px-6 py-3">{{ __('users.fields.status') }}</th>
                                <th scope="col" class="px-6 py-3"><span class="sr-only">{{ __('users.actions.edit') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($users as $user)
                                <tr class="block md:table-row px-4 py-3 md:p-0">
                                    <td class="block md:table-cell md:px-6 md:py-4">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $user->name }}
                                            @if ($user->is(auth()->user()))
                                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ __('users.hints.you') }})</span>
                                            @endif
                                        </div>
                                        <div class="text-gray-500 dark:text-gray-400 break-all">{{ $user->email }}</div>
                                    </td>
                                    <td class="inline-block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4">
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $user->side->label() }}
                                        </span>
                                    </td>
                                    <td class="inline-block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4">
                                        @if ($user->isActive())
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">{{ __('users.statuses.active') }}</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">{{ __('users.statuses.deactivated') }}</span>
                                        @endif
                                    </td>
                                    <td class="block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 md:text-right">
                                        <div class="flex md:justify-end items-center gap-4">
                                            @can('update', $user)
                                                <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('users.actions.edit') }}</a>
                                            @endcan
                                            @can('deactivate', $user)
                                                <form method="POST" action="{{ route('admin.users.deactivation.store', $user) }}">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">{{ __('users.actions.deactivate') }}</button>
                                                </form>
                                            @endcan
                                            @can('reactivate', $user)
                                                <form method="POST" action="{{ route('admin.users.deactivation.destroy', $user) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-green-700 dark:text-green-400 hover:underline">{{ __('users.actions.reactivate') }}</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="px-4 sm:px-0">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
