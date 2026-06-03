<x-layouts.auth title="Mot de passe oublié — Klymap">
    <x-slot:subtitle>Renseignez votre email pour recevoir un lien de réinitialisation.</x-slot:subtitle>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('password.email') }}" novalidate>
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

        <button type="submit" class="btn-auth-submit">Envoyer le lien</button>

        <p class="auth-link-text">
            <a href="{{ route('login') }}" class="auth-link">Retour à la connexion</a>
        </p>
    </form>
</x-layouts.auth>
