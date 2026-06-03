<x-layouts.auth title="Mot de passe oublié — Klymap">
    <x-slot:subtitle>Renseignez votre email pour recevoir un lien de réinitialisation.</x-slot:subtitle>

    <form class="space-y-5" method="POST" action="{{ route('password.email') }}" novalidate>
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

        <button type="submit" class="flex w-full justify-center rounded-lg bg-teal-700 px-3 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700 transition-all active:scale-95">
            Envoyer le lien
        </button>

        <p class="text-center text-sm text-slate-500">
            <a href="{{ route('login') }}" class="font-semibold text-teal-600 hover:text-teal-500 transition-colors">Retour à la connexion</a>
        </p>
    </form>
</x-layouts.auth>
