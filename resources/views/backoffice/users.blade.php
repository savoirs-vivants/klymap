<x-layouts.app title="Backoffice — Utilisateurs">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Utilisateurs</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $users->count() }} compte{{ $users->count() > 1 ? 's' : '' }}
                enregistré{{ $users->count() > 1 ? 's' : '' }}</p>
        </div>
        <button onclick="openModal('modal-create')"
            class="flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-500 text-white text-sm font-semibold rounded-lg transition-all shadow active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Ajouter un utilisateur
        </button>
    </div>

    @if (session('success'))
        <div
            class="mb-4 flex items-center gap-2 px-4 py-3 bg-teal-50 border border-teal-200 rounded-lg text-sm text-teal-800">
            <svg class="w-4 h-4 text-teal-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div
            class="mb-4 flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
            <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-600">Utilisateur</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-600">Email</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-600">Rôle</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-600">Inscription</th>
                    <th class="px-6 py-3.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($users as $user)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-teal-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                    {{ strtoupper(substr($user->firstname, 0, 1)) }}{{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900">{{ $user->firstname }} {{ $user->name }}</p>
                                    @if ($user->id === auth()->id())
                                        <span class="text-xs text-slate-400">Vous</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            @if ($user->role === 'admin')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-100 text-teal-800">Admin</span>
                            @else
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Utilisateur</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-500">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                @if ($user->id !== auth()->id())
                                    <button type="button" data-user="{{ $user->toJson() }}"
                                        onclick="openUserEditModal(this.dataset.user)"
                                        class="p-1.5 text-slate-400 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors"
                                        title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        onclick="openDeleteModal({{ $user->id }}, '{{ $user->firstname }} {{ $user->name }}')"
                                        class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">Aucun utilisateur enregistré.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal Créer --}}
    <div id="modal-create" class="modal-overlay hidden">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="modal-title">Ajouter un utilisateur</h2>
                <button onclick="closeModal('modal-create')" class="modal-close">&times;</button>
            </div>
            <form method="POST" action="{{ route('backoffice.users.store') }}" novalidate>
                @csrf
                <div class="modal-body">
                    @include('backoffice._user-form', ['user' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-create')" class="btn-secondary">Annuler</button>
                    <button type="submit" class="btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Modifier --}}
    <div id="modal-edit" class="modal-overlay hidden">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="modal-title">Modifier l'utilisateur</h2>
                <button onclick="closeModal('modal-edit')" class="modal-close">&times;</button>
            </div>
            <form id="form-edit-user" method="POST" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body">
                    @include('backoffice._user-form', ['user' => null, 'edit' => true])
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-edit')" class="btn-secondary">Annuler</button>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Supprimer --}}
    <div id="modal-delete" class="modal-overlay hidden">
        <div class="modal-box max-w-sm">
            <div class="modal-header">
                <h2 class="modal-title">Supprimer l'utilisateur</h2>
                <button onclick="closeModal('modal-delete')" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-sm text-slate-600">Êtes-vous sûr de vouloir supprimer <strong
                        id="delete-name"></strong> ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('modal-delete')" class="btn-secondary">Annuler</button>
                <form id="form-delete" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

</x-layouts.app>
