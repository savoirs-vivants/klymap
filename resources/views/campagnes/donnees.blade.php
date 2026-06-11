<x-layouts.app title="Données des campagnes">

    {{-- Modal détail point (réutilisé depuis welcome) --}}
    <div id="modal-point" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="modal-point-backdrop"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col z-10 overflow-hidden" style="max-height:92vh">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                <div>
                    <h2 id="modal-point-title" class="text-xl font-bold text-slate-900">Détail du point de mesure</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Analyse ICU complète</p>
                </div>
                <button onclick="window.closePointModal()" class="p-2 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-7">
                <section>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nom du point</label>
                            <input id="point-name" type="text" readonly class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Capteur témoin associé</label>
                            <select id="point-temoin-select" disabled class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl outline-none">
                                <option value="">— Sélectionner —</option>
                            </select>
                        </div>
                    </div>
                </section>
                <section id="point-alerts-section" class="hidden space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Points d'attention</h3>
                    <div id="alert-hum" class="hidden flex items-start gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                        <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <span id="alert-hum-text"></span>
                    </div>
                    <div id="alert-temp-diff" class="hidden flex items-start gap-3 px-4 py-3 bg-orange-50 border border-orange-200 rounded-xl text-sm text-orange-800">
                        <svg class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <span id="alert-temp-diff-text"></span>
                    </div>
                </section>
                <section id="point-chart-temp-section" class="hidden">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Température sht_temp au fil du temps</h3>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <canvas id="chart-temperature" height="110"></canvas>
                    </div>
                </section>
                <section id="point-icu-section" class="hidden">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Calcul de l'ICU</h3>
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
                    <div id="icu-reliability" class="hidden mb-4 flex flex-col items-start gap-3 px-4 py-3 rounded-xl text-sm font-medium border">
                        <div class="flex items-start gap-3">
                            <svg id="icu-rel-icon" class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"></svg>
                            <span id="icu-rel-text"></span>
                        </div>
                        <button type="button" id="btn-icu-ack"
                            class="hidden ml-8 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-semibold transition-colors">
                            Je valide avoir vu l'erreur
                        </button>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 mb-4">
                        <p class="text-xs font-semibold text-slate-500 mb-1">ICU par nuit — moyenne ± écart-type (2h–8h)</p>
                        <canvas id="chart-icu" height="130"></canvas>
                    </div>
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
                </section>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end shrink-0">
                <button onclick="window.closePointModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                    Fermer
                </button>
            </div>
        </div>
    </div>

    {{-- En-tête page --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Données des campagnes</h1>
        <p class="text-sm text-slate-500 mt-1">Résultats d'analyse ICU par groupe</p>
    </div>

    @if($toutesCampagnes !== null)
    {{-- Onglets admin --}}
    <div class="flex gap-2 mb-6">
        <button onclick="showTab('mes')" id="tab-mes" class="px-4 py-2 text-sm font-semibold rounded-lg bg-teal-600 text-white transition-colors">Mes campagnes</button>
        <button onclick="showTab('toutes')" id="tab-toutes" class="px-4 py-2 text-sm font-semibold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">Toutes les campagnes</button>
    </div>
    @endif

    {{-- Mes campagnes --}}
    <div id="section-mes">
        @forelse($mesCampagnes as $campagne)
            @include('campagnes._campagne-card', ['campagne' => $campagne, 'showGestionnaire' => false])
        @empty
            <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center text-slate-400 text-sm">
                Vous n'avez pas encore créé de campagne.
            </div>
        @endforelse
    </div>

    @if($toutesCampagnes !== null)
    {{-- Toutes les campagnes (admin) --}}
    <div id="section-toutes" class="hidden">
        @forelse($toutesCampagnes as $campagne)
            @include('campagnes._campagne-card', ['campagne' => $campagne, 'showGestionnaire' => true])
        @empty
            <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center text-slate-400 text-sm">
                Aucune autre campagne sur le site.
            </div>
        @endforelse
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        function showTab(tab) {
            document.getElementById('section-mes').classList.toggle('hidden', tab !== 'mes');
            document.getElementById('section-toutes').classList.toggle('hidden', tab !== 'toutes');
            document.getElementById('tab-mes').className = tab === 'mes'
                ? 'px-4 py-2 text-sm font-semibold rounded-lg bg-teal-600 text-white transition-colors'
                : 'px-4 py-2 text-sm font-semibold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors';
            document.getElementById('tab-toutes').className = tab === 'toutes'
                ? 'px-4 py-2 text-sm font-semibold rounded-lg bg-teal-600 text-white transition-colors'
                : 'px-4 py-2 text-sm font-semibold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors';
        }

        window.openPointDetail = function(pointId) {
            if (window.openPointModal) {
                fetch(`/api/capteur-points/${pointId}`)
                    .then(r => r.json())
                    .then(p => window.openPointModal(p));
            }
        };
    </script>

</x-layouts.app>
