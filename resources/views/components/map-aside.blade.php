<aside
    class="w-72 bg-teal-800 border-r border-teal-700 flex flex-col z-40 shadow-[4px_0_24px_rgba(0,0,0,0.08)] shrink-0 h-full overflow-y-auto">
    <div class="p-6 flex flex-col gap-8">

        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-teal-300">Filtres : Intensité ICU</h2>
                <button onclick="resetMapFilters()" id="btn-reset-filters"
                    class="hidden text-[10px] font-semibold text-teal-400 hover:text-white transition-colors uppercase tracking-wider">Réinitialiser</button>
            </div>

            <div class="flex flex-col gap-1.5">
                <button onclick="toggleMapFilter(this, 0, 0.5)"
                    class="legend-filter group flex items-center gap-3 w-full text-left transition-all outline-none rounded-lg p-2 hover:bg-teal-700/50">
                    <div
                        class="w-4 h-4 rounded-full bg-blue-900 shadow-sm ring-1 ring-white/20 shrink-0 transition-transform group-hover:scale-110">
                    </div>
                    <span class="text-sm font-medium text-white transition-colors">0 à 0,5 °C</span>
                </button>

                <button onclick="toggleMapFilter(this, 0.5, 1)"
                    class="legend-filter group flex items-center gap-3 w-full text-left transition-all outline-none rounded-lg p-2 hover:bg-teal-700/50">
                    <div
                        class="w-4 h-4 rounded-full bg-blue-500 shadow-sm ring-1 ring-white/20 shrink-0 transition-transform group-hover:scale-110">
                    </div>
                    <span class="text-sm font-medium text-white transition-colors">0,5 à 1 °C</span>
                </button>

                <button onclick="toggleMapFilter(this, 1, 1.5)"
                    class="legend-filter group flex items-center gap-3 w-full text-left transition-all outline-none rounded-lg p-2 hover:bg-teal-700/50">
                    <div
                        class="w-4 h-4 rounded-full bg-pink-400 shadow-sm ring-1 ring-white/20 shrink-0 transition-transform group-hover:scale-110">
                    </div>
                    <span class="text-sm font-medium text-white transition-colors">1 à 1,5 °C</span>
                </button>

                <button onclick="toggleMapFilter(this, 1.5, 2)"
                    class="legend-filter group flex items-center gap-3 w-full text-left transition-all outline-none rounded-lg p-2 hover:bg-teal-700/50">
                    <div
                        class="w-4 h-4 rounded-full bg-orange-500 shadow-sm ring-1 ring-white/20 shrink-0 transition-transform group-hover:scale-110">
                    </div>
                    <span class="text-sm font-medium text-white transition-colors">1,5 à 2 °C</span>
                </button>

                <button onclick="toggleMapFilter(this, 2, 999)"
                    class="legend-filter group flex items-center gap-3 w-full text-left transition-all outline-none rounded-lg p-2 hover:bg-teal-700/50">
                    <div
                        class="w-4 h-4 rounded-full bg-red-500 shadow-sm ring-1 ring-white/20 shrink-0 transition-transform group-hover:scale-110">
                    </div>
                    <span class="text-sm font-medium text-white transition-colors">> 2 °C</span>
                </button>

                <div class="w-full h-px bg-teal-700/60 my-2"></div>

                <div class="flex items-center justify-between">
                    <button onclick="toggleMapFilter(this, 'temoin', null)"
                        class="legend-filter group flex items-center gap-3 text-left transition-all outline-none rounded-lg p-2 hover:bg-teal-700/50">
                        <div
                            class="w-4 h-4 rounded-full bg-slate-900 shadow-sm ring-1 ring-white/20 shrink-0 transition-transform group-hover:scale-110">
                        </div>
                        <span class="text-sm font-medium text-white transition-colors">Capteur Témoin</span>
                    </button>

                    @auth
                        <button onclick="window.activatePlacementMode('temoin')"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-700 hover:bg-teal-600 text-teal-200 hover:text-white text-xs font-semibold transition-colors shrink-0 shadow-sm"
                            title="Placer un capteur témoin sur la carte">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Ajouter
                        </button>
                    @endauth
                </div>

                @if (auth()->check() || session()->has('participant'))
                <div class="flex items-center justify-between">
                    <div class="group flex items-center gap-3 text-left rounded-lg p-2">
                        <div class="w-4 h-4 rounded-full bg-teal-400 shadow-sm ring-1 ring-white/20 shrink-0"></div>
                        <span class="text-sm font-medium text-white">Capteurs Urbains</span>
                    </div>
                    <button onclick="window.activatePlacementMode('point')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-700 hover:bg-teal-600 text-teal-200 hover:text-white text-xs font-semibold transition-colors shrink-0 shadow-sm"
                        title="Placer un capteur urbain sur la carte">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajouter
                    </button>
                </div>
                @endif

                @if (auth()->check() && auth()->user()->isAdmin())
                <div class="flex items-center justify-between">
                    <button onclick="window.toggleMapFilter ? window.toggleMapFilter(this, 'capteur', null) : null"
                        class="legend-filter group flex items-center gap-3 text-left transition-all outline-none rounded-lg p-2 hover:bg-teal-700/50">
                        <div class="w-4 h-4 rounded-full bg-blue-500 shadow-sm ring-1 ring-white/20 shrink-0 transition-transform group-hover:scale-110"></div>
                        <span class="text-sm font-medium text-white transition-colors">Stations météo</span>
                    </button>
                    <button onclick="window.activatePlacementMode('capteur')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-700 hover:bg-teal-600 text-teal-200 hover:text-white text-xs font-semibold transition-colors shrink-0 shadow-sm"
                        title="Localiser une station météo sur la carte">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajouter
                    </button>
                </div>
                @endif
            </div>

            @if(auth()->check() || session()->has("participant"))
                <div class="mt-2 flex items-start gap-2.5 px-3 py-3 rounded-xl bg-teal-900/30 border border-teal-700/50">
                    <svg class="w-4 h-4 text-teal-400 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-[11px] text-teal-200/80 leading-relaxed">Cliquez sur <strong
                            class="text-teal-100">Ajouter</strong> puis sur la carte pour placer un capteur urbain et calculer l'ICU.</p>
                </div>
            @endif
        </div>

        @if(auth()->check() || session()->has('participant'))
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-teal-300 mb-3">Navigation</h2>
                <nav class="flex flex-col gap-1.5">

                    <a href="{{ route('home') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('home') || request()->routeIs('dashboard') ? 'bg-teal-700 text-white shadow-sm' : 'text-teal-100 hover:bg-teal-700/50 hover:text-white' }}">
                        <svg class="w-4 h-4 {{ request()->routeIs('home') || request()->routeIs('dashboard') ? 'text-teal-300' : 'text-teal-400/70' }} shrink-0"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12h18" />
                        </svg>
                        Carte interactive
                    </a>

                    <a href="{{ route('capteurs.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->is('capteurs*') ? 'bg-teal-700 text-white shadow-sm' : 'text-teal-100 hover:bg-teal-700/50 hover:text-white' }}">
                        <svg class="w-4 h-4 {{ request()->is('capteurs*') ? 'text-teal-300' : 'text-teal-400/70' }} shrink-0"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                        </svg>
                        Stations météo
                    </a>

                    <a href="{{ route('comparaison-icu') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('comparaison-icu') ? 'bg-teal-700 text-white shadow-sm' : 'text-teal-100 hover:bg-teal-700/50 hover:text-white' }}">
                        <svg class="w-4 h-4 {{ request()->routeIs('comparaison-icu') ? 'text-teal-300' : 'text-teal-400/70' }} shrink-0"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Comparaison ICU
                    </a>

                    <a href="/donnees-campagnes"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->is('donnees-campagnes*') ? 'bg-teal-700 text-white shadow-sm' : 'text-teal-100 hover:bg-teal-700/50 hover:text-white' }}">
                        <svg class="w-4 h-4 {{ request()->is('donnees-campagnes*') ? 'text-teal-300' : 'text-teal-400/70' }} shrink-0"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Données campagnes
                    </a>

                    @if (auth()->check() && auth()->user()->isAdmin())
                        <a href="{{ route('backoffice.users') }}"
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('backoffice.*') ? 'bg-teal-700 text-white shadow-sm' : 'text-teal-100 hover:bg-teal-700/50 hover:text-white' }}">
                            <svg class="w-4 h-4 {{ request()->routeIs('backoffice.*') ? 'text-teal-300' : 'text-teal-400/70' }} shrink-0"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Backoffice
                        </a>
                    @endif
                </nav>
            </div>
        @endif

    </div>
</aside>
