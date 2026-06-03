<x-layouts.auth title="Connexion — Klymap">
    <x-slot:subtitle>Connectez-vous à votre espace Klymap.</x-slot:subtitle>

    <form class="space-y-5" method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Adresse email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                autofocus
                class="block w-full rounded-lg border-0 py-2.5 px-3 shadow-sm ring-1 ring-inset outline-none transition-all sm:text-sm @error('email') text-red-900 ring-red-300 focus:ring-2 focus:ring-red-500 @else text-slate-900 ring-slate-300 focus:ring-2 focus:ring-teal-500 @enderror"
            >
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-semibold text-slate-700">Mot de passe</label>
                <a href="{{ route('password.request') }}" class="text-xs font-medium text-teal-600 hover:text-teal-500 transition-colors">Mot de passe oublié ?</a>
            </div>
            <div class="relative">
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    class="block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 shadow-sm ring-1 ring-inset outline-none transition-all sm:text-sm @error('password') text-red-900 ring-red-300 focus:ring-2 focus:ring-red-500 @else text-slate-900 ring-slate-300 focus:ring-2 focus:ring-teal-500 @enderror"
                >
                <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600" data-toggle-password="password" aria-label="Afficher le mot de passe">
                    <svg class="w-4 h-4 icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="w-4 h-4 hidden icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center">
            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600 cursor-pointer">
            <label for="remember" class="ml-2 block text-sm text-slate-700 cursor-pointer">Se souvenir de moi</label>
        </div>

        <button type="submit" class="flex w-full justify-center rounded-lg bg-teal-700 px-3 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700 transition-all active:scale-95">
            Se connecter
        </button>

        <p class="text-center text-sm text-slate-500">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="font-semibold text-teal-600 hover:text-teal-500 transition-colors">Créer un compte</a>
        </p>
    </form>
</x-layouts.auth>
