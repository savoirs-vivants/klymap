<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Klymap' }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-teal-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 antialiased">

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <a href="{{ route('home') }}" class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-teal-600 shadow-lg mb-4 hover:bg-teal-500 hover:scale-105 transition-all" aria-label="Retour à l'accueil">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-teal-900">{{ $title ?? 'Klymap' }}</h2>
        @isset($subtitle)
            <p class="mt-2 text-sm text-teal-700">{{ $subtitle }}</p>
        @endisset
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-xl shadow-teal-100 sm:rounded-2xl sm:px-10 border border-teal-100">

            @if (session('status'))
                <div class="mb-6 rounded-lg bg-teal-50 p-4 border border-teal-200">
                    <p class="text-sm font-medium text-teal-800">{{ session('status') }}</p>
                </div>
            @endif

            {{ $slot }}

        </div>
    </div>

</body>
</html>
