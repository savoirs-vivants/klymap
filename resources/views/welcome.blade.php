<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Klymap - Cartographie ICU</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="h-screen w-screen overflow-hidden bg-slate-50 text-slate-800 antialiased flex flex-col" data-auth="{{ auth()->check() ? '1' : '0' }}">

    <x-app-header />

    <div class="flex flex-1 overflow-hidden">

        <x-map-aside />

        <main class="flex-1 relative bg-slate-100">
            <div id="map" class="absolute inset-0 w-full h-full z-10"></div>

            {{-- Géolocalisation --}}
            <button id="btn-geolocate" class="absolute bottom-6 right-6 z-20 w-12 h-12 bg-white rounded-xl shadow-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:text-teal-600 hover:border-teal-200 transition-all active:scale-95 group">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>

            @auth
            {{-- Bannière mode placement --}}
            <div id="placement-banner" class="hidden absolute top-4 left-1/2 -translate-x-1/2 z-30 flex items-center gap-3 bg-slate-900 text-white text-sm font-medium px-5 py-3 rounded-2xl shadow-xl">
                <span id="placement-cursor-icon" class="w-3 h-3 rounded-full bg-white shrink-0 ring-2 ring-white/30 animate-pulse"></span>
                <span id="placement-message">Cliquez sur la carte pour placer le capteur témoin</span>
                <button onclick="window.cancelPlacementMode()" class="ml-2 text-white/50 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Overlay Témoin --}}
            <div id="overlay-temoin" class="hidden absolute bottom-6 left-6 z-40 w-[480px] max-w-[calc(100vw-3rem)] bg-white rounded-2xl shadow-2xl border border-slate-100 flex flex-col overflow-hidden" style="max-height: calc(100vh - 120px);">

                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-slate-900 flex items-center justify-center shrink-0">
                            <div class="w-3 h-3 rounded-full bg-white ring-2 ring-white/30"></div>
                        </div>
                        <div>
                            <h3 id="temoin-title" class="text-sm font-bold text-slate-900">Nouveau capteur témoin</h3>
                            <p id="temoin-mesures-count" class="text-xs text-slate-400"></p>
                        </div>
                    </div>
                    <button onclick="window.closeTemoinOverlay()" class="p-1.5 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Nom du capteur</label>
                        <input
                            id="temoin-name"
                            type="text"
                            placeholder="Ex : Station périphérique nord"
                            class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 border border-slate-200 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-100 outline-none transition-all placeholder:text-slate-300"
                        >
                    </div>

                    <div>
                        <label id="temoin-file-label" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Fichier de données (.txt)</label>
                        <label for="temoin-file-input" class="flex flex-col items-center justify-center gap-2 px-4 py-6 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer bg-slate-50 hover:border-teal-400 hover:bg-teal-50/50 transition-all group">
                            <svg class="w-8 h-8 text-slate-300 group-hover:text-teal-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span id="temoin-file-cta" class="text-sm font-medium text-slate-500 group-hover:text-teal-600 transition-colors">Cliquez pour choisir un fichier</span>
                            <span class="text-xs text-slate-400">Format : index date heure … sht_temp=X sht_hum=X tmp_temp=X</span>
                            <input id="temoin-file-input" type="file" accept=".txt" class="sr-only">
                        </label>
                    </div>

                    <div id="temoin-preview" class="hidden space-y-2">
                        <div class="flex items-center justify-between">
                            <span id="temoin-row-count" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-700"></span>
                            <button id="temoin-export-btn" class="hidden items-center gap-1.5 px-3 py-1.5 bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Exporter CSV
                            </button>
                        </div>

                        <div class="rounded-xl border border-slate-100 overflow-hidden">
                            <div class="overflow-x-auto max-h-52 overflow-y-auto">
                                <table class="w-full text-xs">
                                    <thead class="sticky top-0 bg-slate-50 border-b border-slate-100">
                                        <tr>
                                            <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide text-[10px] whitespace-nowrap">Date / Heure</th>
                                            <th class="px-3 py-2.5 text-right font-semibold text-slate-500 uppercase tracking-wide text-[10px] whitespace-nowrap">Temp. SHT (°C)</th>
                                            <th class="px-3 py-2.5 text-right font-semibold text-slate-500 uppercase tracking-wide text-[10px] whitespace-nowrap">Hum. SHT (%)</th>
                                            <th class="px-3 py-2.5 text-right font-semibold text-slate-500 uppercase tracking-wide text-[10px] whitespace-nowrap">Temp. TMP (°C)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="temoin-tbody" class="divide-y divide-slate-50"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between gap-3 shrink-0">
                    <button id="temoin-delete-btn" class="hidden items-center gap-1.5 px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Supprimer
                    </button>
                    <div class="flex items-center gap-3 ml-auto">
                        <button onclick="window.closeTemoinOverlay()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                            Annuler
                        </button>
                        <button id="temoin-save-btn" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-teal-600 rounded-xl transition-all active:scale-95">
                            Valider
                        </button>
                    </div>
                </div>

            </div>
            @endauth

        </main>
    </div>

    @auth
    {{-- ══════════════════════════════════════════
         Modale Création / Détail point de mesure
    ══════════════════════════════════════════ --}}
    <div id="modal-point" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="modal-point-backdrop"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col z-10 overflow-hidden" style="max-height:92vh">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                <div>
                    <h2 id="modal-point-title" class="text-xl font-bold text-slate-900">Nouveau point de mesure</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Remplissez les informations, importez vos données et analysez l'ICU en temps réel.</p>
                </div>
                <button onclick="window.closePointModal()" class="p-2 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body scrollable --}}
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-7">

                {{-- 1. Infos --}}
                <section>
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">1 — Informations</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nom du point</label>
                            <input id="point-name" type="text" placeholder="Ex : Place du Capitole"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-100 outline-none transition-all placeholder:text-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Capteur témoin associé</label>
                            <select id="point-temoin-select"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-100 outline-none transition-all">
                                <option value="">— Sélectionner —</option>
                            </select>
                        </div>
                    </div>
                </section>

                {{-- 2. Données --}}
                <section>
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">2 — Données de mesure</h3>
                    <label for="point-file-input" class="flex flex-col items-center justify-center gap-2 px-4 py-6 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer bg-slate-50 hover:border-teal-400 hover:bg-teal-50/50 transition-all group">
                        <svg class="w-8 h-8 text-slate-300 group-hover:text-teal-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span id="point-file-cta" class="text-sm font-medium text-slate-500 group-hover:text-teal-600 transition-colors">Cliquez pour choisir un fichier .txt</span>
                        <span class="text-xs text-slate-400">Format : index date heure … sht_temp=X sht_hum=X tmp_temp=X</span>
                        <input id="point-file-input" type="file" accept=".txt" class="sr-only">
                    </label>
                </section>

                {{-- 3. Alertes (masqué jusqu'à import) --}}
                <section id="point-alerts-section" class="hidden space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">3 — Points d'attention</h3>
                    <div id="alert-hum" class="hidden flex items-start gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                        <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <span id="alert-hum-text"></span>
                    </div>
                    <div id="alert-temp-diff" class="hidden flex items-start gap-3 px-4 py-3 bg-orange-50 border border-orange-200 rounded-xl text-sm text-orange-800">
                        <svg class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <span id="alert-temp-diff-text"></span>
                    </div>
                    <div id="flagged-table-wrapper" class="hidden">
                        <p class="text-xs font-semibold text-slate-500 mb-2">Mesures suspectes — décochez pour exclure du calcul ICU :</p>
                        <div class="rounded-xl border border-slate-100 overflow-hidden max-h-48 overflow-y-auto">
                            <table class="w-full text-xs">
                                <thead class="sticky top-0 bg-slate-50 border-b border-slate-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500 uppercase text-[10px]">Inclure</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500 uppercase text-[10px]">Date / Heure</th>
                                        <th class="px-3 py-2 text-right font-semibold text-slate-500 uppercase text-[10px]">SHT Temp</th>
                                        <th class="px-3 py-2 text-right font-semibold text-slate-500 uppercase text-[10px]">Humidité</th>
                                        <th class="px-3 py-2 text-right font-semibold text-slate-500 uppercase text-[10px]">TMP Temp</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500 uppercase text-[10px]">Raison</th>
                                    </tr>
                                </thead>
                                <tbody id="flagged-tbody" class="divide-y divide-slate-50"></tbody>
                            </table>
                        </div>
                        <button id="btn-recalculate" class="mt-2 text-xs font-semibold text-teal-600 hover:text-teal-500 underline">
                            Recalculer avec les sélections
                        </button>
                    </div>
                </section>

                {{-- 4. Graphique température (masqué) --}}
                <section id="point-chart-temp-section" class="hidden">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">4 — Température sht_temp au fil du temps</h3>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <canvas id="chart-temperature" height="110"></canvas>
                    </div>
                </section>

                {{-- 5. Résultats ICU (masqué) --}}
                <section id="point-icu-section" class="hidden">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">5 — Calcul de l'ICU</h3>

                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">ICU moyen</p>
                            <p id="icu-global-val" class="text-3xl font-black text-slate-900">—</p>
                            <p class="text-xs text-slate-400 mt-0.5">°C</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Écart-type</p>
                            <p id="icu-stddev-val" class="text-3xl font-black text-slate-900">—</p>
                            <p class="text-xs text-slate-400 mt-0.5">°C</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Nuits valides</p>
                            <p id="icu-nights-val" class="text-3xl font-black text-slate-900">—</p>
                        </div>
                    </div>

                    <div id="icu-reliability" class="hidden mb-4 flex items-start gap-3 px-4 py-3 rounded-xl text-sm font-medium border">
                        <svg id="icu-rel-icon" class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"></svg>
                        <span id="icu-rel-text"></span>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 mb-4">
                        <p class="text-xs font-semibold text-slate-500 mb-1">ICU par nuit — moyenne ± écart-type (2h–8h)</p>
                        <p class="text-[10px] text-slate-400 mb-3">La zone grise représente la bande de tolérance ±σ autour de la moyenne.</p>
                        <canvas id="chart-icu" height="130"></canvas>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-slate-500 mb-2">Détail des 3 points les plus froids par nuit</p>
                        <div class="rounded-xl border border-slate-100 overflow-hidden max-h-56 overflow-y-auto">
                            <table class="w-full text-xs">
                                <thead class="sticky top-0 bg-slate-50 border-b border-slate-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500 uppercase text-[10px]">Nuit</th>
                                        <th class="px-3 py-2 text-right font-semibold text-red-400 uppercase text-[10px]">Moy. urbain</th>
                                        <th class="px-3 py-2 text-right font-semibold text-emerald-500 uppercase text-[10px]">Moy. témoin</th>
                                        <th class="px-3 py-2 text-right font-semibold text-slate-500 uppercase text-[10px]">ICU nuit</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-400 uppercase text-[10px]">Statut</th>
                                    </tr>
                                </thead>
                                <tbody id="night-tbody" class="divide-y divide-slate-50"></tbody>
                            </table>
                        </div>
                    </div>
                </section>

            </div>

            {{-- Footer fixed --}}
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between shrink-0">
                <button id="point-delete-btn" class="hidden flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Supprimer
                </button>
                <div id="point-progress" class="hidden items-center gap-2 text-xs text-slate-400">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    <span id="point-progress-text">Analyse en cours…</span>
                </div>
                <div class="flex items-center gap-3 ml-auto">
                    <button onclick="window.closePointModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        Annuler
                    </button>
                    <button id="point-save-btn" class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-teal-600 rounded-xl transition-all active:scale-95" disabled>
                        Valider
                    </button>
                </div>
            </div>

        </div>
    </div>
    @endauth

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
</body>
</html>
