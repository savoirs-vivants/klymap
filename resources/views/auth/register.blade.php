<x-layouts.auth title="Inscription — Klymap">
    <x-slot:subtitle>Créez votre compte pour accéder à la plateforme.</x-slot:subtitle>

    <form class="space-y-5" method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="firstname" class="block text-sm font-semibold text-slate-700 mb-1.5">Prénom</label>
                <input
                    type="text"
                    id="firstname"
                    name="firstname"
                    value="{{ old('firstname') }}"
                    autocomplete="given-name"
                    autofocus
                    class="block w-full rounded-lg border-0 py-2.5 px-3 shadow-sm ring-1 ring-inset outline-none transition-all sm:text-sm @error('firstname') text-red-900 ring-red-300 focus:ring-2 focus:ring-red-500 @else text-slate-900 ring-slate-300 focus:ring-2 focus:ring-teal-500 @enderror"
                >
                @error('firstname')
                    <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">Nom</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    autocomplete="family-name"
                    class="block w-full rounded-lg border-0 py-2.5 px-3 shadow-sm ring-1 ring-inset outline-none transition-all sm:text-sm @error('name') text-red-900 ring-red-300 focus:ring-2 focus:ring-red-500 @else text-slate-900 ring-slate-300 focus:ring-2 focus:ring-teal-500 @enderror"
                >
                @error('name')
                    <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Adresse email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                class="block w-full rounded-lg border-0 py-2.5 px-3 shadow-sm ring-1 ring-inset outline-none transition-all sm:text-sm @error('email') text-red-900 ring-red-300 focus:ring-2 focus:ring-red-500 @else text-slate-900 ring-slate-300 focus:ring-2 focus:ring-teal-500 @enderror"
            >
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Mot de passe</label>
            <div class="relative">
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    data-password-input
                    class="block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 shadow-sm ring-1 ring-inset outline-none transition-all sm:text-sm @error('password') text-red-900 ring-red-300 focus:ring-2 focus:ring-red-500 @else text-slate-900 ring-slate-300 focus:ring-2 focus:ring-teal-500 @enderror"
                >
                <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600" data-toggle-password="password" aria-label="Afficher le mot de passe">
                    <svg class="w-4 h-4 icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="w-4 h-4 hidden icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>

            <div class="flex items-center gap-2 mt-2" data-strength-bar>
                <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full w-0 transition-all duration-300" data-strength-fill></div>
                </div>
                <span class="text-[11px] font-semibold text-slate-500 min-w-[60px] text-right" data-strength-label></span>
            </div>

            <ul class="password-rules flex flex-col gap-1 mt-2">
                <li class="text-xs text-slate-400 pl-4 relative transition-colors" data-rule="length">Au moins 8 caractères</li>
                <li class="text-xs text-slate-400 pl-4 relative transition-colors" data-rule="lowercase">Une minuscule</li>
                <li class="text-xs text-slate-400 pl-4 relative transition-colors" data-rule="uppercase">Une majuscule</li>
                <li class="text-xs text-slate-400 pl-4 relative transition-colors" data-rule="number">Un chiffre</li>
                <li class="text-xs text-slate-400 pl-4 relative transition-colors" data-rule="symbol">Un caractère spécial (!@#…)</li>
            </ul>

            @error('password')
                <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">Confirmer le mot de passe</label>
            <div class="relative">
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-500 sm:text-sm transition-all outline-none"
                >
                <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600" data-toggle-password="password_confirmation" aria-label="Afficher le mot de passe">
                    <svg class="w-4 h-4 icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="w-4 h-4 hidden icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="flex w-full justify-center rounded-lg bg-teal-700 px-3 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700 transition-all active:scale-95">
            Créer mon compte
        </button>

        <p class="text-center text-sm text-slate-500">
            Déjà un compte ?
            <a href="{{ route('login') }}" class="font-semibold text-teal-600 hover:text-teal-500 transition-colors">Se connecter</a>
        </p>
    </form>
</x-layouts.auth>
