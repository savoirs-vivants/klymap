<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Klymap - Cartographie ICU</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="h-screen w-screen overflow-hidden bg-slate-50 text-slate-800 antialiased flex flex-col"
    data-auth="{{ auth()->check() || session()->has('participant') ? '1' : '0' }}"
    data-participant="{{ session('participant') ? '1' : '0' }}"
    data-participant-json="{{ session('participant') ? json_encode(session('participant')) : '' }}"
    data-user-id="{{ auth()->id() ?? '' }}"
    data-admin="{{ auth()->check() && auth()->user()->isAdmin() ? '1' : '0' }}">

    <x-app-header />

    <div class="flex flex-1 overflow-hidden relative">

        <div id="map-aside-el" class="aside-wrapper">
            <x-map-aside />
        </div>
        <div class="aside-open-backdrop"
            onclick="document.getElementById('map-aside-el').classList.remove('aside-open')"></div>

        <main class="flex-1 relative bg-slate-100">
            <div id="map" class="absolute inset-0 w-full h-full z-10"></div>

            <div class="relative w-full h-[calc(100vh-64px)]">
                <div id="map" class="w-full h-full z-0"></div>
                {{-- Barre de recherche de ville --}}
                <div class="absolute top-4 left-4 z-[1000]">
                    <div class="relative flex flex-col">
                        <div class="relative flex items-center">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-teal-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input id="city-search-input" type="text" placeholder="Ville ou code postal…" autocomplete="off"
                                class="w-64 pl-10 pr-4 py-2.5 text-sm font-medium text-slate-800 bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.18)] border-2 border-white focus:border-teal-500 focus:ring-0 outline-none transition-all placeholder:text-slate-400">
                        </div>
                        <ul id="city-search-results"
                            class="hidden mt-1.5 bg-white rounded-2xl shadow-[0_8px_30px_rgba(0,0,0,0.15)] border border-slate-100 overflow-hidden divide-y divide-slate-50 max-h-64 overflow-y-auto"></ul>
                    </div>
                </div>

                <div class="absolute bottom-6 left-6 z-[1000] flex items-center gap-3">
                    <button id="btn-geolocate"
                        class="flex items-center justify-center w-12 h-12 bg-white text-gray-700 rounded-full shadow-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>

                    @if (Auth::check())
                        <button id="btn-open-bt"
                            class="flex items-center justify-center h-12 px-4 bg-blue-600 text-white font-medium rounded-full shadow-lg hover:bg-blue-700 transition-colors gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z">
                                </path>
                            </svg>
                            Capteur
                        </button>
                    @endif
                </div>
            </div>

            @if (auth()->check() || session()->has('participant'))
                {{-- Bannière mode placement --}}
                <div id="placement-banner"
                    class="hidden absolute top-4 left-1/2 -translate-x-1/2 z-30 flex items-center gap-3 bg-slate-900 text-white text-sm font-medium px-5 py-3 rounded-2xl shadow-xl">
                    <span id="placement-cursor-icon"
                        class="w-3 h-3 rounded-full bg-white shrink-0 ring-2 ring-white/30 animate-pulse"></span>
                    <span id="placement-message">Cliquez sur la carte pour placer le capteur témoin</span>
                    <button onclick="window.cancelPlacementMode()"
                        class="ml-2 text-white/50 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Overlay Témoin --}}
                <div id="overlay-temoin"
                    class="hidden absolute bottom-6 left-6 z-40 w-[480px] max-w-[calc(100vw-3rem)] bg-white rounded-2xl shadow-2xl border border-slate-100 flex flex-col overflow-hidden"
                    style="max-height: calc(100vh - 120px);">

                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-slate-900 flex items-center justify-center shrink-0">
                                <div class="w-3 h-3 rounded-full bg-white ring-2 ring-white/30"></div>
                            </div>
                            <div>
                                <h3 id="temoin-title" class="text-sm font-bold text-slate-900">Nouveau capteur témoin
                                </h3>
                                <p id="temoin-mesures-count" class="text-xs text-slate-400"></p>
                            </div>
                        </div>
                        <button onclick="window.closeTemoinOverlay()"
                            class="p-1.5 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Nom
                                du capteur</label>
                            <input id="temoin-name" type="text" placeholder="Ex : Station périphérique nord"
                                class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 border border-slate-200 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-100 outline-none transition-all placeholder:text-slate-300">
                        </div>

                        <div>
                            <label id="temoin-file-label"
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Fichier
                                de données (.txt)</label>
                            <label for="temoin-file-input"
                                class="flex flex-col items-center justify-center gap-2 px-4 py-6 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer bg-slate-50 hover:border-teal-400 hover:bg-teal-50/50 transition-all group">
                                <svg class="w-8 h-8 text-slate-300 group-hover:text-teal-400 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span id="temoin-file-cta"
                                    class="text-sm font-medium text-slate-500 group-hover:text-teal-600 transition-colors">Cliquez
                                    pour choisir un fichier</span>
                                <span class="text-xs text-slate-400">Format : index date heure … sht_temp=X sht_hum=X
                                    tmp_temp=X</span>
                                <input id="temoin-file-input" type="file" accept=".txt" class="sr-only">
                            </label>
                        </div>

                        <div id="temoin-preview" class="hidden space-y-2">
                            <div class="flex items-center justify-between">
                                <span id="temoin-row-count"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-700"></span>
                                <button id="temoin-export-btn"
                                    class="hidden items-center gap-1.5 px-3 py-1.5 bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Exporter CSV
                                </button>
                            </div>

                            <div class="rounded-xl border border-slate-100 overflow-hidden">
                                <div class="overflow-x-auto max-h-52 overflow-y-auto">
                                    <table class="w-full text-xs">
                                        <thead class="sticky top-0 bg-slate-50 border-b border-slate-100">
                                            <tr>
                                                <th
                                                    class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide text-[10px] whitespace-nowrap">
                                                    Date / Heure</th>
                                                <th
                                                    class="px-3 py-2.5 text-right font-semibold text-slate-500 uppercase tracking-wide text-[10px] whitespace-nowrap">
                                                    Temp. SHT (°C)</th>
                                                <th
                                                    class="px-3 py-2.5 text-right font-semibold text-slate-500 uppercase tracking-wide text-[10px] whitespace-nowrap">
                                                    Hum. SHT (%)</th>
                                                <th
                                                    class="px-3 py-2.5 text-right font-semibold text-slate-500 uppercase tracking-wide text-[10px] whitespace-nowrap">
                                                    Temp. TMP (°C)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="temoin-tbody" class="divide-y divide-slate-50"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between gap-3 shrink-0">
                        <button id="temoin-delete-btn"
                            class="hidden items-center gap-1.5 px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Supprimer
                        </button>
                        <div class="flex items-center gap-3 ml-auto">
                            <button onclick="window.closeTemoinOverlay()"
                                class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                                Annuler
                            </button>
                            <button id="temoin-save-btn"
                                class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-teal-600 rounded-xl transition-all active:scale-95">
                                Valider
                            </button>
                        </div>
                    </div>

                </div>
            @endif

        </main>
    </div>

    {{-- ══════════════════════════════════════════
         Modale Création / Détail point de mesure
         Visible auth + non-auth (read-only)
    ══════════════════════════════════════════ --}}
    <div id="modal-point" class="hidden fixed inset-0 z-[1500] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="modal-point-backdrop"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col z-10 overflow-hidden"
            style="max-height:92vh">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-4 min-w-0">
                    {{-- Miniature image (visible uniquement si une image est associée au point) --}}
                    <img id="modal-point-image-thumb" src="" alt="Photo"
                        class="hidden w-14 h-14 object-cover rounded-xl border border-slate-200 shrink-0">
                    <div class="min-w-0">
                        <h2 id="modal-point-title" class="text-xl font-bold text-slate-900">Nouveau point de mesure</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Remplissez les informations, importez vos données et
                            analysez l'ICU en temps réel.</p>
                    </div>
                </div>
                <button onclick="window.closePointModal()"
                    class="p-2 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body scrollable --}}
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-7">

                {{-- 1. Infos --}}
                <section>
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">1 — Informations</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nom du point</label>
                            <input id="point-name" type="text" placeholder="Ex : Place du Capitole"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-100 outline-none transition-all placeholder:text-slate-300"
                                @if (!auth()->check() && !session()->has('participant')) readonly @endif>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de l'analyse</label>
                            <input id="point-date" type="date"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-100 outline-none transition-all"
                                @if (!auth()->check() && !session()->has('participant')) readonly @endif>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Capteur témoin associé</label>
                            <select id="point-temoin-select"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-100 outline-none transition-all"
                                @if (!auth()->check() && !session()->has('participant')) disabled @endif>
                                <option value="">— Sélectionner —</option>
                            </select>
                        </div>
                    </div>
                    {{-- Image de l'emplacement (affiché uniquement en mode édition/lecture) --}}
                    @if (auth()->check() || session()->has('participant'))
                    <div id="point-image-section" class="mt-4">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Photo de l'emplacement</label>
                        <div class="flex items-start gap-3">
                            <img id="point-image-preview" src="" alt="Photo emplacement"
                                class="hidden w-24 h-24 object-cover rounded-xl border border-slate-200 shrink-0">
                            <label for="point-image-input"
                                class="flex flex-col items-center justify-center gap-1.5 px-4 py-4 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer bg-slate-50 hover:border-blue-400 hover:bg-blue-50/40 transition-all group flex-1">
                                <svg class="w-6 h-6 text-slate-300 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span id="point-image-cta" class="text-xs font-medium text-slate-400 group-hover:text-blue-500 transition-colors">Ajouter une photo</span>
                                <input id="point-image-input" type="file" accept="image/*" class="sr-only">
                            </label>
                        </div>
                    </div>
                    @endif
                </section>

                {{-- 2. Données --}}
                <section id="point-file-section">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">2 — Données de mesure
                    </h3>
                    <label for="point-file-input"
                        class="flex flex-col items-center justify-center gap-2 px-4 py-6 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer bg-slate-50 hover:border-teal-400 hover:bg-teal-50/50 transition-all group">
                        <svg class="w-8 h-8 text-slate-300 group-hover:text-teal-400 transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span id="point-file-cta"
                            class="text-sm font-medium text-slate-500 group-hover:text-teal-600 transition-colors">Cliquez
                            pour choisir un fichier .txt</span>
                        <span class="text-xs text-slate-400">Format : index date heure … sht_temp=X sht_hum=X
                            tmp_temp=X</span>
                        <input id="point-file-input" type="file" accept=".txt" class="sr-only">
                    </label>
                </section>

                {{-- 3. Alertes (masqué jusqu'à import) --}}
                <section id="point-alerts-section" class="hidden space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">3 — Points d'attention
                    </h3>
                    <div id="alert-hum"
                        class="hidden flex items-start gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                        <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                        <span id="alert-hum-text"></span>
                    </div>
                    <div id="alert-temp-diff"
                        class="hidden flex items-start gap-3 px-4 py-3 bg-orange-50 border border-orange-200 rounded-xl text-sm text-orange-800">
                        <svg class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                        <span id="alert-temp-diff-text"></span>
                    </div>
                    <div id="flagged-table-wrapper" class="hidden">
                        <p class="text-xs font-semibold text-slate-500 mb-2">Mesures suspectes — décochez pour exclure
                            du calcul ICU :</p>
                        <div class="rounded-xl border border-slate-100 overflow-hidden max-h-48 overflow-y-auto">
                            <table class="w-full text-xs">
                                <thead class="sticky top-0 bg-slate-50 border-b border-slate-100">
                                    <tr>
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
                        <button id="btn-acknowledge-errors"
                            class="mt-3 w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 text-xs font-semibold rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            J'ai bien vu les erreurs — ne plus afficher ce tableau
                        </button>
                    </div>
                </section>

                {{-- 4. Graphique température (masqué) --}}
                <section id="point-chart-temp-section" class="hidden">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">4 — Température au fil
                        du temps</h3>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <canvas id="chart-temperature" height="110"></canvas>
                    </div>
                </section>

                {{-- 5. Résultats ICU (masqué) --}}
                <section id="point-icu-section" class="hidden">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">5 — Calcul de l'ICU
                    </h3>

                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">ICU moyen
                            </p>
                            <p id="icu-global-val" class="text-3xl font-black text-slate-900">—</p>
                            <p class="text-xs text-slate-400 mt-0.5">°C</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Écart-type
                            </p>
                            <p id="icu-stddev-val" class="text-3xl font-black text-slate-900">—</p>
                            <p class="text-xs text-slate-400 mt-0.5">°C</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-center">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Nuits
                                valides</p>
                            <p id="icu-nights-val" class="text-3xl font-black text-slate-900">—</p>
                        </div>
                    </div>

                    <div id="icu-reliability"
                        class="hidden mb-4 flex items-start gap-3 px-4 py-3 rounded-xl text-sm font-medium border">
                        <svg id="icu-rel-icon" class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24"></svg>
                        <span id="icu-rel-text"></span>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 mb-4">
                        <p class="text-xs font-semibold text-slate-500 mb-1">ICU par nuit — moyenne ± écart-type
                            (2h–8h)</p>
                        <p class="text-[10px] text-slate-400 mb-3">La zone grise représente la bande de tolérance ±σ
                            autour de la moyenne.</p>
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
                                        <th class="px-3 py-2 text-[10px]"></th>
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
                @if (auth()->check() || session()->has('participant'))
                    <button id="point-delete-btn"
                        class="hidden flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Supprimer
                    </button>
                    <div id="point-progress" class="hidden items-center gap-2 text-xs text-slate-400">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                        </svg>
                        <span id="point-progress-text">Analyse en cours…</span>
                    </div>
                @endif
                <div class="flex items-center gap-3 ml-auto">
                    <button onclick="window.closePointModal()"
                        class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        Fermer
                    </button>
                    @if (auth()->check() || session()->has('participant'))
                        <button id="point-save-btn"
                            class="px-5 py-2 text-sm font-semibold text-white bg-slate-900 hover:bg-teal-600 rounded-xl transition-all active:scale-95"
                            disabled>
                            Valider
                        </button>
                    @endif
                </div>
            </div>

        </div>
    </div>

    @if (auth()->check() && auth()->user()->isAdmin())
        {{-- Overlay : Localiser un capteur par DevEui --}}
        <div id="overlay-capteur-locate"
            class="hidden fixed inset-0 z-[1500] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="window.closeCapteurLocateModal()"></div>
            <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">

                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-500 flex items-center justify-center shrink-0">
                            <div class="w-3 h-3 rounded-full bg-white ring-2 ring-white/30"></div>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Localiser un capteur</h3>
                    </div>
                    <button onclick="window.closeCapteurLocateModal()"
                        class="p-1.5 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-5 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">DevEui</label>
                        <input id="capteur-locate-deveui" type="text" placeholder="Ex : 0004A30B001C3F2A"
                            class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-slate-300">
                    </div>
                    <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200">
                        <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span id="capteur-locate-coords" class="text-sm text-slate-600 font-mono"></span>
                    </div>
                    <input type="hidden" id="capteur-locate-lat">
                    <input type="hidden" id="capteur-locate-lng">
                    <p id="capteur-locate-error" class="hidden text-xs text-red-500 font-medium"></p>
                </div>

                <div class="px-5 pb-5 flex justify-end gap-2">
                    <button onclick="window.closeCapteurLocateModal()"
                        class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors">
                        Annuler
                    </button>
                    <button id="capteur-locate-save-btn"
                        class="px-4 py-2 text-sm font-semibold bg-blue-500 hover:bg-blue-600 text-white rounded-xl transition-colors disabled:opacity-50">
                        Valider
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if (Auth::check())
        {{-- Overlay Bluetooth (synchronisation des données capteur) --}}
        <div id="bt-modal"
            class="fixed bottom-0 left-0 right-0 z-[2000] translate-y-full transition-transform duration-300 bg-white rounded-t-3xl shadow-2xl border-t border-slate-100 flex flex-col"
            style="max-height: 85vh;">

            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Synchronisation Bluetooth</h3>
                        <p class="text-xs text-slate-400">Station : <span id="valUid">—</span></p>
                    </div>
                </div>
                <button id="bt-close"
                    class="p-1.5 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="bg-slate-50 rounded-xl px-3 py-2.5 border border-slate-100">
                        <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">Température</p>
                        <p id="valTemp" class="text-sm font-bold text-orange-500">—</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl px-3 py-2.5 border border-slate-100">
                        <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">Humidité</p>
                        <p id="valHum" class="text-sm font-bold text-blue-500">— <span class="text-[10px] font-normal text-slate-400">%</span></p>
                    </div>
                    <div class="bg-slate-50 rounded-xl px-3 py-2.5 border border-slate-100">
                        <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">Vent</p>
                        <p id="valVent" class="text-sm font-bold text-cyan-500">— <span class="text-[10px] font-normal text-slate-400">km/h</span></p>
                    </div>
                    <div class="bg-slate-50 rounded-xl px-3 py-2.5 border border-slate-100">
                        <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">Direction</p>
                        <p id="valDir" class="text-sm font-bold text-amber-500">— <span class="text-[10px] font-normal text-slate-400"></span></p>
                    </div>
                    <div class="bg-slate-50 rounded-xl px-3 py-2.5 border border-slate-100">
                        <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">Pression</p>
                        <p id="valPress" class="text-sm font-bold text-violet-500">— <span class="text-[10px] font-normal text-slate-400">hPa</span></p>
                    </div>
                    <div class="bg-slate-50 rounded-xl px-3 py-2.5 border border-slate-100">
                        <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">Pluie</p>
                        <p id="valPluie" class="text-sm font-bold text-emerald-500">— <span class="text-[10px] font-normal text-slate-400">mm</span></p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button id="bt-action-connect"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition-colors">
                        1. Connecter le PCB
                    </button>
                    <button id="bt-action-start"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition-colors">
                        Démarrer
                    </button>
                    <button id="bt-action-stop"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition-colors">
                        Arrêter
                    </button>
                    <button id="bt-action-download"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition-colors">
                        Télécharger le log
                    </button>
                    <button id="bt-action-sync"
                        class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl transition-colors">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Synchroniser avec la BDD
                    </button>
                </div>

                <div id="bt-sync-status" class="hidden mt-2 p-3 rounded-xl text-xs font-mono"></div>

                <div>
                    <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1.5">Console</p>
                    <textarea id="bt-console" readonly
                        class="w-full h-40 text-xs font-mono text-slate-600 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 resize-none"
                        placeholder="Prêt. Connectez la station pour démarrer la réception des données…"></textarea>
                </div>
            </div>
        </div>

        <script>
            window.btSyncUrl = @json(route('capteurs.bluetooth.sync'));
        </script>
    @endif

    {{-- Lightbox image plein écran --}}
    <div id="img-lightbox"
        class="hidden fixed inset-0 z-[9999] bg-black/90 flex items-center justify-center p-4 cursor-zoom-out"
        onclick="document.getElementById('img-lightbox').classList.add('hidden')">
        <img id="img-lightbox-src" src="" alt=""
            class="max-w-full max-h-full object-contain rounded-xl shadow-2xl select-none"
            onclick="event.stopPropagation()">
        <button class="absolute top-4 right-4 p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-xl transition-colors"
            onclick="document.getElementById('img-lightbox').classList.add('hidden')">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
</body>

</html>
