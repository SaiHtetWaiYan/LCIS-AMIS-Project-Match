<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('users.edit') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-xl">
                    @csrf
                    @method('PUT')
                    @include('admin.users.partials.form', ['user' => $user])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
