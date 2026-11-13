<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ __('app.short_name') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-blue-gray-900">
        <div class="min-h-screen flex flex-col bg-gray-50">
            @include('layouts.navigation')

            <main class="flex-grow pt-20 lg:pt-24 pb-12">
                <div class="container mx-auto px-4 sm:px-6 lg:px-10 space-y-6">
                    <x-flash-messages />

                    @isset($header)
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
