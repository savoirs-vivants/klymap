<x-layouts.auth title="Connexion — Klymap">
    <x-slot:subtitle>Connectez-vous à votre espace Klymap.</x-slot:subtitle>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">Adresse email</label>
            <input
                class="form-input @error('email') is-error @enderror"
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                autofocus
            >
            @error('email')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <div class="form-label-row">
                <label class="form-label" for="password">Mot de passe</label>
                <a href="{{ route('password.request') }}" class="auth-link auth-link--small">Mot de passe oublié ?</a>
            </div>
            <div class="input-password-wrapper">
                <input
                    class="form-input @error('password') is-error @enderror"
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                >
                <button type="button" class="btn-toggle-password" data-toggle-password="password" aria-label="Afficher le mot de passe">
                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="icon-eye-off hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
            @error('password')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group form-group--inline">
            <label class="form-checkbox">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <span>Se souvenir de moi</span>
            </label>
        </div>

        <button type="submit" class="btn-auth-submit">Se connecter</button>

        <p class="auth-link-text">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="auth-link">Créer un compte</a>
        </p>
    </form>
</x-layouts.auth>
