@php
    $links = [
        ['route' => 'dashboard', 'active' => 'dashboard', 'label' => __('app.nav.dashboard'), 'visible' => true],
        ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'label' => __('app.nav.users'), 'visible' => auth()->user()->can('viewAny', App\Models\User::class)],
        ['route' => 'admin.sync.index', 'active' => 'admin.sync.*', 'label' => __('app.nav.sync'), 'visible' => auth()->user()->can('viewAny', App\Models\SyncRun::class)],
    ];
@endphp

<header class="fixed top-0 inset-x-0 z-50">
    <nav x-data="{ mobileOpen: false }" class="block w-full px-4 py-2 bg-white shadow-md lg:px-8 lg:py-3">
        <div class="container mx-auto flex flex-wrap items-center justify-between text-slate-800">
            <a href="{{ route('dashboard') }}" class="mr-4 flex items-center py-1.5 text-base font-semibold text-slate-600">
                <img src="{{ asset('img/logo/logo_transparent_x64.png') }}" alt="" class="h-8 w-8">
                <span class="ml-2 whitespace-nowrap">{{ __('app.short_name') }}</span>
            </a>

            <button type="button" @click="mobileOpen = ! mobileOpen" :aria-expanded="mobileOpen.toString()"
                class="lg:hidden inline-flex items-center justify-center rounded-md border border-gray-200 p-2 text-slate-600 hover:bg-gray-100"
                aria-label="{{ __('app.nav.toggle') }}">
                <svg x-show="! mobileOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg x-show="mobileOpen" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <ul class="hidden lg:flex lg:flex-1 lg:items-center lg:gap-6 lg:ml-6">
                @foreach ($links as $link)
                    @if ($link['visible'])
                        <li>
                            <a href="{{ route($link['route']) }}"
                                @class([
                                    'flex items-center p-1 text-sm',
                                    'font-semibold text-slate-900 border-b-2 border-green-500' => request()->routeIs($link['active']),
                                    'text-slate-600 hover:text-slate-900' => ! request()->routeIs($link['active']),
                                ])
                                @if (request()->routeIs($link['active'])) aria-current="page" @endif>
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>

            <div class="hidden lg:flex items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-full text-sm text-slate-600 hover:text-gray-900 focus:outline-none">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-gray-100 text-sm font-semibold text-blue-gray-500">
                                {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="max-w-[12rem] truncate">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 fill-current text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-gray-500">{{ Auth::user()->side->label() }}</div>
                        <x-dropdown-link :href="route('profile.edit')">{{ __('app.nav.account') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('app.nav.logout') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>

        <div x-show="mobileOpen" x-transition x-cloak class="lg:hidden border-t border-gray-100 mt-3 pt-3">
            <div class="container mx-auto space-y-4">
                <ul class="rounded-lg border border-gray-200 bg-white py-2">
                    @foreach ($links as $link)
                        @if ($link['visible'])
                            <li>
                                <a href="{{ route($link['route']) }}"
                                    @class([
                                        'block px-4 py-2 text-sm',
                                        'font-semibold text-slate-900 bg-gray-50' => request()->routeIs($link['active']),
                                        'text-slate-700 hover:bg-gray-50' => ! request()->routeIs($link['active']),
                                    ])>
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>

                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <div class="flex items-center justify-between text-sm text-slate-700">
                        <span class="font-medium">{{ Auth::user()->name }} · {{ Auth::user()->side->label() }}</span>
                        <a href="{{ route('profile.edit') }}" class="rounded-md px-2 py-1 hover:bg-gray-50">{{ __('app.nav.account') }}</a>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-slate-100 px-3 py-2 text-sm text-slate-700 hover:bg-slate-200">
                            {{ __('app.nav.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
</header>
