<header
    class="h-16 bg-white border-b border-teal-600 flex items-center justify-between px-6 shrink-0 z-50 relative shadow-md">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-teal-400 rounded-lg flex items-center justify-center shadow-inner">
            <svg class="w-5 h-5 text-teal-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                </path>
            </svg>
        </div>
        <span class="font-mono text-xl font-bold tracking-tight text-black">Klymap</span>
    </div>

    <div class="flex items-center gap-4">
        @auth
            <button id="btn-lancer-campagne"
                class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-teal-50 border border-teal-200 text-teal-700 text-sm font-medium hover:bg-teal-100 hover:border-teal-300 transition-all duration-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Lancer une campagne
            </button>
            <div id="modal-campagne" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
                <div id="modal-campagne-backdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden z-10">
                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                        <h3 class="text-lg font-black text-[#222a60] font-grotesk">Lancer une campagne</h3>
                        <button id="modal-campagne-close"
                            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div id="campagne-step1" class="p-6">
                        <form id="form-campagne" class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nom de la campagne</label>
                                <input id="campagne-nom" type="text" required placeholder="Ex : Sortie Garonne 3ème B"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#222a60]/25 focus:border-[#222a60] transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre de groupes <span
                                        class="font-normal text-slate-400">(0 = mode individuel)</span></label>
                                <input id="campagne-nb-groupes" type="number" min="0" max="26" value="0"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#222a60]/25 focus:border-[#222a60] transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Date de fin <span
                                        class="font-normal text-slate-400">(optionnel)</span></label>
                                <input id="campagne-date-fin" type="date" min="{{ now()->addDay()->format('Y-m-d') }}"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#222a60]/25 focus:border-[#222a60] transition-all">
                            </div>
                            <p id="campagne-error" class="text-sm text-red-500 font-medium"></p>
                            <button type="submit"
                                class="w-full py-3 rounded-xl bg-[#222a60] text-white font-bold text-sm hover:bg-[#1a2050] transition-colors">
                                Créer la campagne
                            </button>
                        </form>
                    </div>

                    <div id="campagne-step2" class="hidden p-6 text-center space-y-5">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mx-auto">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Campagne créée ! Partagez ce code :</p>
                            <div class="flex items-center justify-center gap-3 mt-2">
                                <span id="campagne-code-display"
                                    class="font-mono text-3xl font-black text-[#222a60] tracking-widest"></span>
                                <button id="campagne-copy-btn"
                                    class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-600 transition-colors">
                                    Copier
                                </button>
                            </div>
                        </div>
                        <p id="campagne-groups-info" class="text-sm text-slate-500"></p>
                        <button onclick="document.getElementById('modal-campagne').classList.add('hidden')"
                            class="w-full py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        @endauth

        <a href="/code"
            class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-slate-200 text-slate-600 text-sm font-medium hover:border-slate-300 hover:bg-slate-50 transition-all duration-200">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
            J'ai un code
        </a>

        @auth
            <div class="relative" id="user-menu-wrapper">
                <button id="user-menu-btn" type="button"
                    class="w-9 h-9 rounded-full bg-teal-500 flex items-center justify-center text-white text-sm font-bold tracking-wide hover:bg-teal-400 transition-colors shadow focus:outline-none focus:ring-2 focus:ring-teal-300 focus:ring-offset-2"
                    aria-haspopup="true" aria-expanded="false">
                    {{ strtoupper(substr(auth()->user()->firstname, 0, 1)) }}{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </button>

                <div id="user-menu"
                    class="hidden absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-slate-100 py-1 z-50"
                    role="menu">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->firstname }}
                            {{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</p>
                    </div>

                    <div class="space-y-1">
                        <a href="#"
                            class="flex items-center gap-3 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold text-gray-500 hover:bg-gray-50 hover:text-[#0F143A] transition-colors no-underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Modifier mon profil
                        </a>
                        <a href="{{ route('campagnes.index') }}"
                            class="flex items-center gap-3 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold text-gray-500 hover:bg-gray-50 hover:text-[#0F143A] transition-colors no-underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Gestion des campagnes
                        </a>
                    </div>
                    <div class="h-px bg-gray-100 my-1 sm:my-2 mx-3 sm:mx-4"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold text-red-500 hover:bg-red-50 transition-colors cursor-pointer border-none outline-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('login') }}"
                class="flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-500 text-white text-sm font-semibold rounded-lg transition-all shadow hover:shadow-md active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Mon compte
            </a>
        @endauth
    </div>
</header>
