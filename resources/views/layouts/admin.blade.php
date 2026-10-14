<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Kumon Timeslot System')</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @stack('styles')
</head>

<body class="bg-gray-100 font-sans antialiased">

    <div class="min-h-screen w-full overflow-x-hidden">

        {{-- Sidebar --}}
        @include('panel.sidebar')

        {{-- Main content area --}}
        {{-- Do not add w-full here because lg:ml-64 already uses space --}}
        <div class="min-h-screen min-w-0 lg:ml-64">

            {{-- Header --}}
            @include('panel.header')

            <main class="min-w-0 overflow-x-hidden">
                <div class="page-content w-full max-w-full px-4 sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </main>

        </div>

    </div>

    @stack('scripts')

</body>
</html>
