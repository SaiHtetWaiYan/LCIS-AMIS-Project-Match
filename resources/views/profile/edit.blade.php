<x-app-layout>
    <x-slot name="title">{{ __('users.profile.title') }}</x-slot>

    <x-card :title="__('users.profile.details')" :subtitle="__('users.profile.details_hint')">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3 text-sm max-w-3xl">
            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('users.fields.name') }}</dt>
                <dd class="mt-1 text-blue-gray-900 dark:text-gray-100">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('users.fields.email') }}</dt>
                <dd class="mt-1 text-blue-gray-900 dark:text-gray-100 break-all">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('users.fields.side') }}</dt>
                <dd class="mt-1 text-blue-gray-900 dark:text-gray-100">{{ $user->side->label() }}</dd>
            </div>
        </dl>
    </x-card>

    <x-card>
        <div class="max-w-xl">
            @include('profile.partials.update-password-form')
        </div>
    </x-card>
</x-app-layout>
