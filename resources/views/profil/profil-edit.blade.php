<x-layouts.app title="Modifier le profil">

<div class="max-w-2xl mx-auto space-y-6">

    @if (session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-4">
        <p class="text-sm text-emerald-700 font-medium">{{ session('success') }}</p>
    </div>
    @endif

    @if (session('success_password'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-4">
        <p class="text-sm text-emerald-700 font-medium">{{ session('success_password') }}</p>
    </div>
    @endif

    <form method="POST" action="{{ route('profil.update') }}">
        @csrf
        @method('PUT')

        @if ($errors->hasAny(['firstname', 'name', 'email']))
        <div class="bg-red-50 border border-red-200 rounded-2xl px-5 py-4 mb-4">
            <ul class="space-y-1">
                @foreach (['firstname', 'name', 'email'] as $field)
                    @foreach ($errors->get($field) as $error)
                        <li class="text-sm text-red-600 font-medium">{{ $error }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_rgba(34,42,96,0.06)] p-6">
            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-5">
                Informations du compte
            </p>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1.5">Prénom</label>
                    <input type="text" name="firstname" value="{{ old('firstname', $user->firstname) }}" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 outline-none focus:border-[#222a60] focus:bg-white transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1.5">Nom</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 outline-none focus:border-[#222a60] focus:bg-white transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1.5">Adresse e-mail</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 outline-none focus:border-[#222a60] focus:bg-white transition-colors">
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 mt-4">
            <a href="{{ route('dashboard') }}"
                class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors no-underline">
                Annuler
            </a>
            <button type="submit"
                class="px-6 py-2.5 rounded-xl bg-teal-800 hover:bg-[#1a2050] text-white text-sm font-semibold flex items-center gap-2 transition-colors">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Enregistrer
            </button>
        </div>
    </form>

    {{-- ── Mot de passe ── --}}
    <form method="POST" action="{{ route('profil.update-password') }}" id="registerForm" novalidate>
        @csrf
        @method('PUT')

        @if ($errors->hasAny(['current_password', 'password']))
        <div class="bg-red-50 border border-red-200 rounded-2xl px-5 py-4 mb-4">
            <ul class="space-y-1">
                @foreach (['current_password', 'password'] as $field)
                    @foreach ($errors->get($field) as $error)
                        <li class="text-sm text-red-600 font-medium">{{ $error }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_rgba(34,42,96,0.06)] p-6">
            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-5">
                Modifier le mot de passe
            </p>

            {{-- Mot de passe actuel --}}
            <div class="mb-4">
                <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1.5">
                    Mot de passe actuel
                </label>
                <div class="relative">
                    <input id="current_password" name="current_password" type="password"
                        autocomplete="current-password"
                        placeholder="Votre mot de passe actuel"
                        class="w-full px-4 py-2.5 pr-11 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 outline-none focus:border-[#222a60] focus:bg-white transition-colors
                               @error('current_password') border-red-300 bg-red-50 @enderror">

                    {{-- Modification ici : utilisation de data-toggle-password et ajout des 2 SVGs --}}
                    <button type="button" data-toggle-password="current_password"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="icon-eye hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg class="icon-eye-off w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                    </button>
                </div>
            </div>

            {{-- Nouveau mot de passe (Ajout de la classe form-group pour le script de force) --}}
            <div class="mb-4 form-group">
                <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1.5">
                    Nouveau mot de passe
                </label>
                <div class="relative">
                    {{-- Modification ici : Ajout de data-password-input --}}
                    <input id="password" name="password" type="password" data-password-input
                        autocomplete="new-password"
                        placeholder="8 caractères minimum"
                        class="w-full px-4 py-2.5 pr-11 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 outline-none focus:border-[#222a60] focus:bg-white transition-colors">

                    <button type="button" data-toggle-password="password"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="icon-eye hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg class="icon-eye-off w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                    </button>
                </div>

                {{-- Barre de force : Ajout des attributs data-* attendus par le JS --}}
                <div class="mt-2" data-strength-bar>
                    <div class="bg-slate-100 h-1.5 rounded-full overflow-hidden w-full">
                        <div class="h-full transition-all duration-300" data-strength-fill style="width: 0%"></div>
                    </div>
                    <p class="text-[10px] mt-1 font-mono transition-colors duration-200 text-slate-400" data-strength-label>
                        Saisissez un mot de passe
                    </p>
                </div>
            </div>

            {{-- Confirmation --}}
            <div>
                <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1.5">
                    Confirmer le nouveau mot de passe
                </label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        autocomplete="new-password"
                        placeholder="Répétez le nouveau mot de passe"
                        class="w-full px-4 py-2.5 pr-11 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 outline-none focus:border-[#222a60] focus:bg-white transition-colors">

                    <button type="button" data-toggle-password="password_confirmation"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="icon-eye hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg class="icon-eye-off w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <button type="submit"
                class="px-6 py-2.5 rounded-xl bg-teal-800 hover:bg-[#1a2050] text-white text-sm font-semibold flex items-center gap-2 transition-colors">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Changer le mot de passe
            </button>
        </div>
    </form>

</div>

</x-layouts.app>
