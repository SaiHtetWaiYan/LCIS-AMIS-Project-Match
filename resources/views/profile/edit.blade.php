<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('users.profile.title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <section class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ __('users.profile.details') }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('users.profile.details_hint') }}
                        </p>
                    </header>

                    <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('users.fields.name') }}</dt>
                            <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('users.fields.email') }}</dt>
                            <dd class="mt-1 text-gray-900 dark:text-gray-100 break-all">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('users.fields.side') }}</dt>
                            <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $user->side->label() }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
