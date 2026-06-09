<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Klymap' }}</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-50 antialiased flex flex-col">

    <x-app-header />

    <div class="flex flex-1 overflow-hidden relative">
        <div id="app-aside-el" class="aside-wrapper">
            <x-map-aside :show-filters="false" />
        </div>
        <div class="aside-open-backdrop" onclick="document.getElementById('app-aside-el').classList.remove('aside-open')"></div>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div class="max-w-6xl mx-auto">
                {{ $slot }}
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
