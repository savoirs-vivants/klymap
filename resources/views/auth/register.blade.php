<x-layouts.auth title="Inscription — Klymap">
    <x-slot:subtitle>Créez votre compte pour accéder à la plateforme.</x-slot:subtitle>

    <form class="auth-form" method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="form-group-row">
            <div class="form-group">
                <label class="form-label" for="firstname">Prénom</label>
                <input
                    class="form-input @error('firstname') is-error @enderror"
                    type="text"
                    id="firstname"
                    name="firstname"
                    value="{{ old('firstname') }}"
                    autocomplete="given-name"
                    autofocus
                >
                @error('firstname')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="name">Nom</label>
                <input
                    class="form-input @error('name') is-error @enderror"
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    autocomplete="family-name"
                >
                @error('name')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Adresse email</label>
            <input
                class="form-input @error('email') is-error @enderror"
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
            >
            @error('email')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Mot de passe</label>
            <div class="input-password-wrapper">
                <input
                    class="form-input @error('password') is-error @enderror"
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    data-password-input
                >
                <button type="button" class="btn-toggle-password" data-toggle-password="password" aria-label="Afficher le mot de passe">
                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="icon-eye-off hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>

            <div class="password-strength" data-strength-bar>
                <div class="strength-bar-track">
                    <div class="strength-bar-fill" data-strength-fill></div>
                </div>
                <span class="strength-label" data-strength-label></span>
            </div>

            <ul class="password-rules">
                <li data-rule="length">Au moins 8 caractères</li>
                <li data-rule="lowercase">Une minuscule</li>
                <li data-rule="uppercase">Une majuscule</li>
                <li data-rule="number">Un chiffre</li>
                <li data-rule="symbol">Un caractère spécial (!@#…)</li>
            </ul>

            @error('password')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
            <div class="input-password-wrapper">
                <input
                    class="form-input"
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                >
                <button type="button" class="btn-toggle-password" data-toggle-password="password_confirmation" aria-label="Afficher le mot de passe">
                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="icon-eye-off hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-auth-submit">Créer mon compte</button>

        <p class="auth-link-text">
            Déjà un compte ?
            <a href="{{ route('login') }}" class="auth-link">Se connecter</a>
        </p>
    </form>
</x-layouts.auth>
