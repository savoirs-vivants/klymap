<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="form-label-bo" for="{{ ($edit ?? false) ? 'edit_firstname' : 'firstname' }}">Prénom</label>
        <input
            class="form-input-bo"
            type="text"
            id="{{ ($edit ?? false) ? 'edit_firstname' : 'firstname' }}"
            name="firstname"
            value="{{ old('firstname', $user?->firstname) }}"
            required
        >
    </div>
    <div>
        <label class="form-label-bo" for="{{ ($edit ?? false) ? 'edit_name' : 'name' }}">Nom</label>
        <input
            class="form-input-bo"
            type="text"
            id="{{ ($edit ?? false) ? 'edit_name' : 'name' }}"
            name="name"
            value="{{ old('name', $user?->name) }}"
            required
        >
    </div>
</div>

<div>
    <label class="form-label-bo" for="{{ ($edit ?? false) ? 'edit_email' : 'email' }}">Email</label>
    <input
        class="form-input-bo"
        type="email"
        id="{{ ($edit ?? false) ? 'edit_email' : 'email' }}"
        name="email"
        value="{{ old('email', $user?->email) }}"
        required
    >
</div>

<div>
    <label class="form-label-bo" for="{{ ($edit ?? false) ? 'edit_role' : 'role' }}">Rôle</label>
    <select
        class="form-input-bo"
        id="{{ ($edit ?? false) ? 'edit_role' : 'role' }}"
        name="role"
    >
        <option value="user" {{ old('role', $user?->role) === 'user' ? 'selected' : '' }}>Utilisateur</option>
        <option value="admin" {{ old('role', $user?->role) === 'admin' ? 'selected' : '' }}>Administrateur</option>
    </select>
</div>

<div>
    <label class="form-label-bo" for="{{ ($edit ?? false) ? 'edit_password' : 'password' }}">
        Mot de passe
        @if ($edit ?? false)
            <span class="text-slate-400 font-normal">(laisser vide pour ne pas changer)</span>
        @endif
    </label>
    <input
        class="form-input-bo"
        type="password"
        id="{{ ($edit ?? false) ? 'edit_password' : 'password' }}"
        name="password"
        {{ ($edit ?? false) ? '' : 'required' }}
    >
</div>

<div>
    <label class="form-label-bo" for="{{ ($edit ?? false) ? 'edit_password_confirmation' : 'password_confirmation' }}">Confirmer le mot de passe</label>
    <input
        class="form-input-bo"
        type="password"
        id="{{ ($edit ?? false) ? 'edit_password_confirmation' : 'password_confirmation' }}"
        name="password_confirmation"
    >
</div>
