<header class="h-16 bg-white border-b border-teal-600 flex items-center justify-between px-6 shrink-0 z-50 relative shadow-md">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-teal-400 rounded-lg flex items-center justify-center shadow-inner">
            <svg class="w-5 h-5 text-teal-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <span class="font-mono text-xl font-bold tracking-tight text-black">Klymap</span>
    </div>

    <div class="flex items-center gap-4">
        <button class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-blue/25 text-blue text-sm font-medium hover:border-blue/60 hover:bg-blue/5 transition-all duration-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    J'ai un code
        </button>

        @auth
            <div class="relative" id="user-menu-wrapper">
                <button
                    id="user-menu-btn"
                    type="button"
                    class="w-9 h-9 rounded-full bg-teal-400 flex items-center justify-center text-teal-900 text-sm font-bold tracking-wide hover:bg-teal-300 transition-colors shadow focus:outline-none focus:ring-2 focus:ring-teal-300 focus:ring-offset-2 focus:ring-offset-teal-700"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    {{ strtoupper(substr(auth()->user()->firstname, 0, 1)) }}{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </button>

                <div
                    id="user-menu"
                    class="hidden absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-slate-100 py-1 z-50"
                    role="menu"
                >
                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->firstname }} {{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2"
                            role="menuitem"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Se déconnecter
                        </button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('login') }}" class="flex items-center gap-2 px-4 py-2 bg-teal-400 hover:bg-teal-300 text-teal-900 text-sm font-semibold rounded-lg transition-all shadow hover:shadow-md active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Mon compte
            </a>
        @endauth
    </div>
</header>
