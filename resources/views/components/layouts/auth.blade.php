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
<body class="auth-body">

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-card-header">
                <a href="{{ route('home') }}" class="auth-logo">Klymap</a>
                <p class="auth-subtitle">{{ $subtitle ?? '' }}</p>
            </div>

            {{ $slot }}
        </div>
    </div>

</body>
</html>
