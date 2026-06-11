<header class="h-16 bg-white border-b border-teal-600 flex items-center justify-between px-4 sm:px-6 shrink-0 z-50 relative shadow-md">

    <div class="flex items-center gap-2 sm:gap-3">
        {{-- Burger mobile/tablette --}}
        <button
            class="aside-burger p-2 text-teal-700 hover:bg-teal-50 rounded-lg transition-colors"
            onclick="(function(){var a=document.getElementById('map-aside-el')||document.getElementById('app-aside-el');if(a)a.classList.toggle('aside-open');})()"
            aria-label="Menu"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
        </button>

        <span class="font-mono text-xl font-bold tracking-tight text-black">Klymap</span>
    </div>

    <div class="flex items-center gap-2 sm:gap-3">
        @auth
            <button id="btn-lancer-campagne"
                class="flex items-center gap-1.5 px-3 py-2 rounded-full bg-teal-50 border border-teal-200 text-teal-700 text-sm font-medium hover:bg-teal-100 transition-all duration-200">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span class="max-md:hidden">Lancer une campagne</span>
            </button>

            @if($creatorCampagnes->isNotEmpty())
                @php $activeCampagneNom = $activeCampagneId ? $creatorCampagnes->firstWhere('id', $activeCampagneId)?->nom : null; @endphp
                <div class="relative" data-dropdown-wrapper>
                    <button type="button" data-dropdown-toggle
                        class="flex items-center gap-1.5 px-3 py-2 rounded-full border border-slate-200 text-slate-600 text-sm font-medium hover:border-slate-300 hover:bg-slate-50 transition-all"
                        aria-haspopup="true" aria-expanded="false">
                        <svg class="w-3.5 h-3.5 shrink-0 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span class="max-md:hidden">{{ $activeCampagneNom ?? 'Aucune campagne' }}</span>
                        <svg class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div data-dropdown-menu class="hidden absolute right-0 mt-2 w-60 bg-white rounded-xl shadow-xl border border-slate-100 py-1 z-50" role="menu">
                        <div class="px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Campagne active</div>
                        <button type="button" data-campagne-activate=""
                            class="w-full text-left px-4 py-2.5 text-sm transition-colors flex items-center justify-between {{ !$activeCampagneId ? 'text-teal-700 font-semibold bg-teal-50' : 'text-slate-700 hover:bg-slate-50' }}" role="menuitem">
                            Aucune campagne
                            @if(!$activeCampagneId)
                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </button>
                        @foreach($creatorCampagnes as $c)
                            <button type="button" data-campagne-activate="{{ $c->id }}"
                                class="w-full text-left px-4 py-2.5 text-sm transition-colors flex items-center justify-between {{ $activeCampagneId === $c->id ? 'text-teal-700 font-semibold bg-teal-50' : 'text-slate-700 hover:bg-slate-50' }}" role="menuitem">
                                <span class="truncate">{{ $c->nom }}</span>
                                @if($activeCampagneId === $c->id)
                                    <svg class="w-4 h-4 text-teal-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Modal campagne --}}
            <div id="modal-campagne" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
                <div id="modal-campagne-backdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden z-10">
                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                        <h3 class="text-lg font-black text-teal-800">Lancer une campagne</h3>
                        <button id="modal-campagne-close" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div id="campagne-step1" class="p-6">
                        <form id="form-campagne" class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nom de la campagne</label>
                                <input id="campagne-nom" type="text" required placeholder="Ex : Sortie Garonne 3ème B" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre de groupes <span class="font-normal text-slate-400">(0 = individuel)</span></label>
                                <input id="campagne-nb-groupes" type="number" min="0" max="26" value="0" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Date de fin <span class="font-normal text-slate-400">(optionnel)</span></label>
                                <input id="campagne-date-fin" type="date" min="{{ now()->addDay()->format('Y-m-d') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all">
                            </div>
                            <p id="campagne-error" class="text-sm text-red-500 font-medium"></p>
                            <button type="submit" class="w-full py-3 rounded-xl bg-teal-700 text-white font-bold text-sm hover:bg-teal-600 transition-colors">Créer la campagne</button>
                        </form>
                    </div>
                    <div id="campagne-step2" class="hidden p-6 text-center space-y-5">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mx-auto">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Campagne créée ! Partagez ce code :</p>
                            <div class="flex items-center justify-center gap-3 mt-2">
                                <span id="campagne-code-display" class="font-mono text-3xl font-black text-teal-800 tracking-widest"></span>
                                <button id="campagne-copy-btn" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-600 transition-colors">Copier</button>
                            </div>
                        </div>
                        <p id="campagne-groups-info" class="text-sm text-slate-500"></p>
                        <button onclick="document.getElementById('modal-campagne').classList.add('hidden')" class="w-full py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">Fermer</button>
                    </div>
                </div>
            </div>
        @endauth

        {{-- J'ai un code --}}
        <a href="/code" class="flex items-center gap-1.5 px-3 py-2 rounded-full border border-slate-200 text-slate-600 text-sm font-medium hover:border-slate-300 hover:bg-slate-50 transition-all">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            <span class="max-md:hidden">J'ai un code</span>
        </a>

        @auth
            <div class="relative" id="user-menu-wrapper">
                <button id="user-menu-btn" type="button"
                    class="w-9 h-9 rounded-full bg-teal-500 flex items-center justify-center text-white text-sm font-bold tracking-wide hover:bg-teal-400 transition-colors shadow focus:outline-none focus:ring-2 focus:ring-teal-300 focus:ring-offset-2"
                    aria-haspopup="true" aria-expanded="false">
                    {{ strtoupper(substr(auth()->user()->firstname, 0, 1)) }}{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </button>

                <div id="user-menu" class="hidden absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-slate-100 py-1 z-50" role="menu">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->firstname }} {{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    @if(Route::has('profil'))
                    <a href="{{ route('profil') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-2" role="menuitem">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Modifier le profil
                    </a>
                    @endif
                    @if(Route::has('campagnes.index'))
                    <a href="{{ route('campagnes.index') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-2" role="menuitem">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Gestion des campagnes
                    </a>
                    @endif
                    <div class="border-t border-slate-100 my-1"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2" role="menuitem">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Se déconnecter
                        </button>
                    </form>
                </div>
            </div>
        @elseif(session()->has('participant'))
            {{-- Session participant --}}
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-teal-50 border border-teal-200 rounded-full text-sm text-teal-700 font-medium">
                    <div class="w-5 h-5 rounded-full bg-teal-500 flex items-center justify-center text-white text-[10px] font-bold shrink-0">
                        {{ strtoupper(substr($activeParticipant['pseudo'], 0, 1)) }}
                    </div>
                    <span class="max-md:hidden">{{ $activeParticipant['campagne_nom'] }}</span>
                    @if($activeParticipant['id_groupe'] > 0)
                        <span class="max-md:hidden text-teal-500">· Groupe {{ chr(64 + $activeParticipant['id_groupe']) }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('participant.logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium rounded-full transition-colors"
                        title="Quitter la session">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                        <span class="max-md:hidden">Quitter</span>
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}" class="flex items-center gap-1.5 px-3 py-2 bg-teal-600 hover:bg-teal-500 text-white text-sm font-semibold rounded-lg transition-all shadow active:scale-95">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="max-md:hidden">Mon compte</span>
            </a>
        @endauth
    </div>
</header>
