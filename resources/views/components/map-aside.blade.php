@props(['showFilters' => true])

<aside class="w-16 md:w-60 lg:w-72 h-full bg-teal-800 border-r border-teal-700 flex flex-col z-40 shadow-[4px_0_24px_rgba(0,0,0,0.08)]">
    <div class="p-3 lg:p-6 flex flex-col gap-6 lg:gap-8 overflow-y-auto flex-1">

        @if($showFilters)
        <div>
            <h2 class="aside-text text-xs font-bold uppercase tracking-wider text-teal-300 mb-4">Légende : Intensité ICU</h2>
            <div class="flex flex-col gap-3">

                <button onclick="window.toggleMapFilter(this, 0, 0.5)" class="legend-filter active-filter flex items-center gap-2 lg:gap-3 w-full text-left rounded-lg px-1 lg:px-2 py-1 hover:bg-teal-700/50 transition-colors" title="0 à 0,5 °C">
                    <div class="w-4 h-4 rounded-full bg-[#1e3a8a] shrink-0 ring-1 ring-white/10"></div>
                    <span class="aside-text text-sm font-medium text-teal-100">0 à 0,5 °C</span>
                </button>

                <button onclick="window.toggleMapFilter(this, 0.5, 1)" class="legend-filter active-filter flex items-center gap-2 lg:gap-3 w-full text-left rounded-lg px-1 lg:px-2 py-1 hover:bg-teal-700/50 transition-colors" title="0,5 à 1 °C">
                    <div class="w-4 h-4 rounded-full bg-[#3b82f6] shrink-0 ring-1 ring-white/10"></div>
                    <span class="aside-text text-sm font-medium text-teal-100">0,5 à 1 °C</span>
                </button>

                <button onclick="window.toggleMapFilter(this, 1, 1.5)" class="legend-filter active-filter flex items-center gap-2 lg:gap-3 w-full text-left rounded-lg px-1 lg:px-2 py-1 hover:bg-teal-700/50 transition-colors" title="1 à 1,5 °C">
                    <div class="w-4 h-4 rounded-full bg-[#f472b6] shrink-0 ring-1 ring-white/10"></div>
                    <span class="aside-text text-sm font-medium text-teal-100">1 à 1,5 °C</span>
                </button>

                <button onclick="window.toggleMapFilter(this, 1.5, 2)" class="legend-filter active-filter flex items-center gap-2 lg:gap-3 w-full text-left rounded-lg px-1 lg:px-2 py-1 hover:bg-teal-700/50 transition-colors" title="1,5 à 2 °C">
                    <div class="w-4 h-4 rounded-full bg-[#f97316] shrink-0 ring-1 ring-white/10"></div>
                    <span class="aside-text text-sm font-medium text-teal-100">1,5 à 2 °C</span>
                </button>

                <button onclick="window.toggleMapFilter(this, 2, 999)" class="legend-filter active-filter flex items-center gap-2 lg:gap-3 w-full text-left rounded-lg px-1 lg:px-2 py-1 hover:bg-teal-700/50 transition-colors" title="> 2 °C">
                    <div class="w-4 h-4 rounded-full bg-[#ef4444] shrink-0 ring-1 ring-white/10"></div>
                    <span class="aside-text text-sm font-medium text-teal-100">> 2 °C</span>
                </button>

                <div class="w-full h-px bg-teal-600 my-1"></div>

                <div class="flex items-center justify-between gap-1">
                    <div class="flex items-center gap-2 lg:gap-3" title="Capteur Témoin">
                        <div class="w-4 h-4 rounded-full bg-slate-900 shrink-0 ring-1 ring-white/10"></div>
                        <span class="aside-text text-sm font-medium text-teal-100">Capteur Témoin</span>
                    </div>
                    @auth
                    <button onclick="window.activatePlacementMode('temoin')" class="flex items-center gap-1 px-1.5 lg:px-2.5 py-1 rounded-lg bg-teal-700 hover:bg-teal-600 text-teal-200 hover:text-white text-xs font-semibold transition-colors shrink-0" title="Ajouter un capteur témoin">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span class="aside-text">Ajouter</span>
                    </button>
                    @endauth
                </div>

                @auth
                <div class="aside-text flex items-start gap-2 px-2 py-2 rounded-lg bg-teal-700/40 border border-teal-600/30 mt-1">
                    <svg class="w-3.5 h-3.5 text-teal-300 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs text-teal-200 leading-relaxed">Cliquez sur la carte pour placer un <strong class="text-white">point de mesure</strong>.</p>
                </div>
                @endauth
            </div>
        </div>
        @endif

        @auth
        <div>
            <h2 class="aside-text text-xs font-bold uppercase tracking-wider text-teal-300 mb-3">Navigation</h2>
            <nav class="flex flex-col gap-1">
                <a href="{{ route('home') }}" class="flex items-center gap-2 lg:gap-2.5 px-2 lg:px-3 py-2 rounded-lg text-teal-100 hover:bg-teal-700 transition-colors {{ request()->routeIs('home', 'dashboard') ? 'bg-teal-700' : '' }}" title="Carte">
                    <svg class="w-4 h-4 text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span class="aside-text text-sm font-medium">Carte</span>
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('backoffice.users') }}" class="flex items-center gap-2 lg:gap-2.5 px-2 lg:px-3 py-2 rounded-lg text-teal-100 hover:bg-teal-700 transition-colors {{ request()->routeIs('backoffice.*') ? 'bg-teal-700' : '' }}" title="Backoffice">
                    <svg class="w-4 h-4 text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="aside-text text-sm font-medium">Backoffice</span>
                </a>
                @endif
            </nav>
        </div>
        @endauth

    </div>
</aside>
