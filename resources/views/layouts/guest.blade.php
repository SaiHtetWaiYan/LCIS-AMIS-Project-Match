<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('app.short_name') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10 bg-gray-100">
            <div class="flex flex-col items-center text-center">
                <img src="{{ asset('img/logo/logo_transparent_x256.png') }}" alt="" class="h-20 w-20">
                <h1 class="mt-3 text-xl font-semibold text-slate-700">{{ __('app.name') }}</h1>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-md rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
