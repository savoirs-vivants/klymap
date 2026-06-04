<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Klymap — Rejoindre une session</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/join-session.js'])
</head>

<body class="bg-slate-50 font-grotesk text-slate-900 antialiased min-h-screen flex flex-col">

    <nav class="bg-white border-b border-slate-100 px-6 h-14 flex items-center justify-between">
        <a href="/" class="font-bold text-[#222a60] text-lg tracking-tight">Klymap</a>
        <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-slate-700 transition-colors">
            Connexion compte
        </a>
    </nav>

    <main class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-sm">

            <div class="text-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-[#222a60]/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-[#222a60]" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-black text-[#222a60]">Rejoindre une session</h1>
                <p class="text-sm text-slate-500 mt-1">Entrez le code fourni par votre enseignant</p>
            </div>

            <p id="join-error" class="mb-4 text-sm text-red-500 font-medium text-center min-h-[20px]">
                @if (session('error'))
                    {{ session('error') }}
                @endif
            </p>

            <div id="join-step1" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">
                <form id="form-code" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Code de la session</label>
                        <input id="input-code" type="text" required maxlength="8" placeholder="Ex : AB3CX7YZ"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-center font-mono text-xl font-black uppercase tracking-widest text-[#222a60] focus:outline-none focus:ring-2 focus:ring-[#222a60]/25 focus:border-[#222a60] transition-all"
                            oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <button type="submit"
                        class="w-full py-3 rounded-xl bg-[#222a60] text-white font-bold text-sm hover:bg-[#1a2050] transition-colors">
                        Valider le code
                    </button>
                </form>
            </div>

            <div id="join-step2" class="hidden bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">
                <div class="flex items-center gap-2 mb-1">
                    <button id="btn-back-step1" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div>
                        <p class="text-[11px] text-slate-400 font-mono uppercase tracking-wider">Campagne</p>
                        <p id="join-campagne-nom" class="text-sm font-bold text-[#222a60]"></p>
                    </div>
                </div>

                <form id="form-register" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nom / Prénom</label>
                        <input id="input-pseudo" type="text" required maxlength="100" placeholder="Ex : Marie Dupont"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#222a60]/25 focus:border-[#222a60] transition-all">
                    </div>

                    <div id="groupe-select-wrapper" class="hidden">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Choisir votre groupe</label>
                        <select id="input-groupe" value="0"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#222a60]/25 focus:border-[#222a60] transition-all cursor-pointer">
                            <option value="0">—</option>
                        </select>
                    </div>

                    <button type="submit"
                        class="w-full py-3 rounded-xl bg-[#222a60] text-white font-bold text-sm hover:bg-[#1a2050] transition-colors">
                        Rejoindre la session
                    </button>
                </form>
            </div>

        </div>
    </main>

</body>

</html>
