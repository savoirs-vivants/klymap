<aside class="w-72 bg-teal-800 border-r border-teal-700 flex flex-col z-40 shadow-[4px_0_24px_rgba(0,0,0,0.08)]">
    <div class="p-6 flex flex-col gap-8">

        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-teal-300 mb-4">Légende : Intensité ICU</h2>
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-[#1e3a8a] shadow-sm ring-1 ring-white/10 shrink-0"></div>
                    <span class="text-sm font-medium text-teal-100">0 à 0,5 °C</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-[#3b82f6] shadow-sm ring-1 ring-white/10 shrink-0"></div>
                    <span class="text-sm font-medium text-teal-100">0,5 à 1 °C</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-[#f472b6] shadow-sm ring-1 ring-white/10 shrink-0"></div>
                    <span class="text-sm font-medium text-teal-100">1 à 1,5 °C</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-[#f97316] shadow-sm ring-1 ring-white/10 shrink-0"></div>
                    <span class="text-sm font-medium text-teal-100">1,5 à 2 °C</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-[#ef4444] shadow-sm ring-1 ring-white/10 shrink-0"></div>
                    <span class="text-sm font-medium text-teal-100">> 2 °C</span>
                </div>

                <div class="w-full h-px bg-teal-600 my-1"></div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded-full bg-slate-900 shadow-sm ring-1 ring-white/10 shrink-0"></div>
                        <span class="text-sm font-medium text-teal-100">Capteur Témoin</span>
                    </div>
                    @auth
                    <button
                        onclick="window.activatePlacementMode('temoin')"
                        class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-700 hover:bg-teal-600 text-teal-200 hover:text-white text-xs font-semibold transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Ajouter
                    </button>
                    @endauth
                </div>

                @auth
                <div class="mt-3 flex items-start gap-2 px-2 py-2.5 rounded-lg bg-teal-700/40 border border-teal-600/30">
                    <svg class="w-3.5 h-3.5 text-teal-300 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs text-teal-200 leading-relaxed">Cliquez sur la carte pour placer un <strong class="text-white">point de mesure</strong> et calculer l'ICU.</p>
                </div>
                @endauth
            </div>
        </div>

        @auth
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-teal-300 mb-3">Navigation</h2>
                <nav class="flex flex-col gap-1">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-teal-100 hover:bg-teal-700 transition-colors {{ request()->routeIs('home') || request()->routeIs('dashboard') ? 'bg-teal-700' : '' }}">
                        <svg class="w-4 h-4 text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12h18"/></svg>
                        Carte
                    </a>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('backoffice.users') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-teal-100 hover:bg-teal-700 transition-colors {{ request()->routeIs('backoffice.*') ? 'bg-teal-700' : '' }}">
                            <svg class="w-4 h-4 text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Backoffice
                        </a>
                    @endif
                </nav>
            </div>
        @endauth

    </div>
</aside>
