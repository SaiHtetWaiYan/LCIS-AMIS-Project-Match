<x-app-layout>
    <x-slot name="title">{{ __('users.edit') }}</x-slot>

    <x-card :title="__('users.edit')">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-xl">
            @csrf
            @method('PUT')
            @include('admin.users.partials.form', ['user' => $user])
        </form>
    </x-card>
</x-app-layout>
