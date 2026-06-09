<x-layouts.app>
    <div class="max-w-4xl mx-auto px-6 py-8">

        {{-- En-tête --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Comparaison des ICU</h1>
            <p class="mt-1 text-sm text-slate-500">Comparez l'intensité ICU entre différentes conditions. Min. 3 points par condition, max. 6 conditions.</p>
        </div>

        {{-- Liste des conditions --}}
        <div id="conditions-list" class="flex flex-col gap-3 mb-6"></div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button id="btn-add-condition"
                class="flex items-center gap-2 px-5 py-3 rounded-2xl bg-teal-700 hover:bg-teal-600 text-white text-sm font-semibold transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Ajouter une condition
            </button>
            <button id="btn-generate-chart"
                class="hidden px-5 py-3 rounded-2xl bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold transition-colors shadow-sm">
                Voir le graphique comparatif
            </button>
        </div>

        {{-- Chart --}}
        <div id="chart-section" class="hidden mt-10 bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-base font-bold text-slate-900">ICU moyen par condition (±&nbsp;écart type)</h2>
                <div class="flex items-center gap-2">
                    <button id="btn-export-png"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        PNG
                    </button>
                    <button id="btn-reset-chart"
                        class="text-xs font-semibold text-slate-400 hover:text-red-500 transition-colors px-2 py-1.5">
                        Réinitialiser
                    </button>
                </div>
            </div>
            <div id="icu-chart-wrapper" style="position:relative;height:360px;">
                <canvas id="icu-comparison-chart"></canvas>
            </div>
        </div>

    </div>

    {{-- Modal ajout de condition --}}
    <div id="modal-condition" class="hidden fixed inset-0 z-[1500] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="modal-condition-backdrop"></div>
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 flex flex-col overflow-hidden" style="max-height:90vh">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
                <h3 id="modal-condition-title" class="text-sm font-bold text-slate-900">Nouvelle condition</h3>
                <button id="modal-condition-close"
                    class="p-1.5 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Nom de la condition</label>
                    <input id="cond-name" type="text" placeholder="Ex : Zone urbaine dense"
                        class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 border border-slate-200 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-100 outline-none transition-all placeholder:text-slate-300">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Points de mesure</label>
                        <span id="cond-count-badge" class="text-xs font-semibold text-teal-600 bg-teal-50 px-2 py-0.5 rounded-full">0 sélectionné(s)</span>
                    </div>
                    <p class="text-xs text-slate-400 mb-3">Min. 3 points avec valeur ICU. Les points déjà utilisés dans une autre condition sont grisés.</p>
                    <div id="cond-points-tree" class="space-y-3 max-h-72 overflow-y-auto pr-1"></div>
                    <p id="cond-error" class="hidden mt-2 text-xs text-red-500 font-medium"></p>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 shrink-0 flex justify-end gap-2">
                <button id="modal-condition-cancel"
                    class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors">
                    Annuler
                </button>
                <button id="modal-condition-save"
                    class="px-5 py-2 text-sm font-semibold bg-teal-700 hover:bg-teal-600 text-white rounded-xl transition-colors">
                    Valider la condition
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (() => {
        const PALETTE = ['#0d9488','#2563eb','#f59e0b','#ef4444','#8b5cf6','#ec4899'];
        let allPoints  = [];
        let conditions = [];
        let editingIdx = null;
        let chartInst  = null;

        const mean = arr => arr.reduce((s,v) => s+v, 0) / arr.length;
        const std  = arr => { const m = mean(arr); return Math.sqrt(arr.reduce((s,v) => s+(v-m)**2, 0)/arr.length); };

        // IDs déjà assignés à une condition (hors condition en cours d'édition)
        function usedIds() {
            return new Set(
                conditions
                    .filter((_, i) => i !== editingIdx)
                    .flatMap(c => c.points.map(p => p.id))
            );
        }

        /* ── Chargement points ──────────────────────────────────────── */
        let pointsReady = false;
        let pendingModalOpen = null;

        fetch('/api/capteur-points')
            .then(r => r.json())
            .then(data => {
                allPoints   = data.filter(p => p.icu_value != null);
                pointsReady = true;
                if (pendingModalOpen !== null) {
                    openModal(pendingModalOpen);
                    pendingModalOpen = null;
                }
            });

        /* ── Arbre de sélection ─────────────────────────────────────── */
        function renderPointsTree(selectedIds = []) {
            const tree    = document.getElementById('cond-points-tree');
            const blocked = usedIds();
            tree.innerHTML = '';

            const groups = {};
            allPoints.forEach(p => {
                const g = p.temoin_name || 'Sans zone';
                (groups[g] = groups[g] || []).push(p);
            });

            if (!Object.keys(groups).length) {
                tree.innerHTML = '<p class="text-sm text-slate-400 italic">Aucun point avec valeur ICU disponible.</p>';
                return;
            }

            Object.entries(groups).sort(([a],[b]) => a.localeCompare(b)).forEach(([zone, pts]) => {
                const wrap = document.createElement('div');
                wrap.className = 'rounded-xl border border-slate-200 overflow-hidden';
                wrap.innerHTML = `<div class="px-3 py-2 bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wide flex items-center gap-2">
                    <svg class="w-3 h-3 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>${zone}</div>`;

                pts.forEach(p => {
                    const isBlocked = blocked.has(p.id);
                    const isChecked = selectedIds.includes(p.id);
                    const row = document.createElement('label');
                    row.className = `flex items-center gap-3 px-3 py-2.5 border-b border-slate-50 last:border-0 transition-colors ${isBlocked ? 'opacity-40 cursor-not-allowed bg-slate-50' : 'hover:bg-slate-50 cursor-pointer'}`;
                    row.innerHTML = `
                        <input type="checkbox" value="${p.id}" ${isChecked ? 'checked' : ''} ${isBlocked ? 'disabled' : ''}
                            class="cond-point-cb w-4 h-4 rounded text-teal-600 border-slate-300 focus:ring-teal-500 ${isBlocked ? 'cursor-not-allowed' : 'cursor-pointer'}">
                        <span class="flex-1 text-sm ${isBlocked ? 'text-slate-400 line-through' : 'text-slate-700'}">${p.name}</span>
                        <span class="text-xs font-semibold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full">${Number(p.icu_value).toFixed(2)} °C</span>`;
                    wrap.appendChild(row);
                });
                tree.appendChild(wrap);
            });

            tree.querySelectorAll('.cond-point-cb').forEach(cb => cb.addEventListener('change', updateCount));
            updateCount();
        }

        function updateCount() {
            const n = document.querySelectorAll('.cond-point-cb:checked').length;
            document.getElementById('cond-count-badge').textContent = `${n} sélectionné(s)`;
        }

        /* ── Modal ──────────────────────────────────────────────────── */
        function openModal(idx = null) {
            if (!pointsReady) {
                pendingModalOpen = idx;
                // Affiche un feedback visuel pendant le chargement
                const btn = document.getElementById('btn-add-condition');
                const orig = btn.textContent;
                btn.textContent = 'Chargement…';
                btn.disabled = true;
                const wait = setInterval(() => {
                    if (pointsReady) {
                        clearInterval(wait);
                        btn.textContent = orig;
                        btn.disabled = false;
                    }
                }, 100);
                return;
            }
            editingIdx = idx;
            const cond = idx !== null ? conditions[idx] : null;
            document.getElementById('modal-condition-title').textContent = cond ? `Modifier : ${cond.name}` : 'Nouvelle condition';
            document.getElementById('cond-name').value = cond?.name ?? '';
            document.getElementById('cond-error').classList.add('hidden');
            renderPointsTree(cond ? cond.points.map(p => p.id) : []);
            document.getElementById('modal-condition').classList.remove('hidden');
            setTimeout(() => document.getElementById('cond-name').focus(), 50);
        }

        function closeModal() {
            document.getElementById('modal-condition').classList.add('hidden');
            editingIdx = null;
        }

        document.getElementById('modal-condition-close').addEventListener('click', closeModal);
        document.getElementById('modal-condition-cancel').addEventListener('click', closeModal);
        document.getElementById('modal-condition-backdrop').addEventListener('click', closeModal);

        /* ── Sauvegarder ────────────────────────────────────────────── */
        document.getElementById('modal-condition-save').addEventListener('click', () => {
            const name = document.getElementById('cond-name').value.trim();
            const cbx  = [...document.querySelectorAll('.cond-point-cb:checked')];
            const err  = document.getElementById('cond-error');

            if (!name) { err.textContent = 'Donnez un nom à cette condition.'; err.classList.remove('hidden'); return; }
            if (cbx.length < 3) { err.textContent = 'Sélectionnez au minimum 3 points.'; err.classList.remove('hidden'); return; }

            err.classList.add('hidden');
            const pts  = allPoints.filter(p => cbx.map(c => parseInt(c.value)).includes(p.id));
            const cond = { name, points: pts };
            editingIdx !== null ? (conditions[editingIdx] = cond) : conditions.push(cond);
            closeModal();
            renderConditions();
        });

        /* ── Rendu conditions ───────────────────────────────────────── */
        function renderConditions() {
            const list = document.getElementById('conditions-list');
            list.innerHTML = '';

            conditions.forEach((cond, i) => {
                const vals = cond.points.map(p => p.icu_value);
                const m = mean(vals), s = std(vals);
                const dot = PALETTE[i % PALETTE.length];
                const card = document.createElement('div');
                card.className = 'flex items-center gap-4 bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4';
                card.innerHTML = `
                    <div class="w-3 h-3 rounded-full shrink-0" style="background:${dot}"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-900">${cond.name}</p>
                        <p class="text-xs text-slate-400 mt-0.5">${cond.points.length} points · ICU moy. <strong class="text-slate-700">${m.toFixed(2)} °C</strong> · σ <strong class="text-slate-700">${s.toFixed(2)}</strong></p>
                    </div>
                    <button data-edit="${i}" class="p-1.5 text-slate-300 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors" title="Modifier">
                        <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button data-del="${i}" class="p-1.5 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                        <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>`;
                list.appendChild(card);
            });

            document.getElementById('btn-add-condition').classList.toggle('hidden', conditions.length >= 6);
            document.getElementById('btn-generate-chart').classList.toggle('hidden', conditions.length < 2);
        }

        document.getElementById('conditions-list').addEventListener('click', e => {
            const editBtn = e.target.closest('[data-edit]');
            const delBtn  = e.target.closest('[data-del]');
            if (editBtn) openModal(parseInt(editBtn.dataset.edit));
            if (delBtn) { conditions.splice(parseInt(delBtn.dataset.del), 1); renderConditions(); }
        });

        document.getElementById('btn-add-condition').addEventListener('click', () => openModal());

        /* ── Graphique ──────────────────────────────────────────────── */
        function renderChart() {
            const section = document.getElementById('chart-section');
            section.classList.remove('hidden');

            const labels = conditions.map(c => c.name);
            const means  = conditions.map(c => mean(c.points.map(p => parseFloat(p.icu_value))));
            const stdevs = conditions.map(c => std(c.points.map(p => parseFloat(p.icu_value))));
            const colors = conditions.map((_, i) => PALETTE[i % PALETTE.length]);
            const yMin   = Math.max(0, Math.min(...means.map((m, i) => m - stdevs[i])) - 0.1);
            const yMax   = Math.max(...means.map((m, i) => m + stdevs[i])) + 0.1;

            const bgColors = colors.map(c => {
                const r = parseInt(c.slice(1,3),16);
                const g = parseInt(c.slice(3,5),16);
                const b = parseInt(c.slice(5,7),16);
                return `rgba(${r},${g},${b},0.75)`;
            });

            setTimeout(() => {
                if (chartInst) { chartInst.destroy(); chartInst = null; }

                const wrapper = document.getElementById('icu-chart-wrapper');
                wrapper.innerHTML = '<canvas id="icu-comparison-chart"></canvas>';

                chartInst = new Chart(document.getElementById('icu-comparison-chart'), {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'ICU moyen (°C)',
                            data:  means,
                            backgroundColor: bgColors,
                            borderColor:     colors,
                            borderWidth:     2,
                            borderRadius:    8,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title:      items => items[0].label,
                                    label:      item  => ` ICU moy. : ${item.parsed.y.toFixed(2)} °C`,
                                    afterLabel: item  => [
                                        ` σ = ${stdevs[item.dataIndex].toFixed(2)} °C`,
                                        ` n = ${conditions[item.dataIndex].points.length} points`,
                                    ],
                                },
                            },
                        },
                        scales: {
                            y: {
                                min:   yMin,
                                max:   yMax,
                                title: { display: true, text: 'ICU (°C)', font: { weight: '600', size: 12 } },
                                grid:  { color: '#f1f5f9' },
                                ticks: { callback: v => v.toFixed(2) + ' °C' },
                            },
                            x: { grid: { display: false } },
                        },
                    },
                });

                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 50);
        }

        document.getElementById('btn-generate-chart').addEventListener('click', renderChart);

        /* ── Export PNG ─────────────────────────────────────────────── */
        document.getElementById('btn-export-png').addEventListener('click', () => {
            if (!chartInst) return;
            const a = document.createElement('a');
            a.href     = chartInst.canvas.toDataURL('image/png');
            a.download = `comparaison-icu-${new Date().toISOString().slice(0,10)}.png`;
            a.click();
        });

        /* ── Réinitialiser ──────────────────────────────────────────── */
        document.getElementById('btn-reset-chart').addEventListener('click', () => {
            conditions = [];
            renderConditions();
            document.getElementById('chart-section').classList.add('hidden');
            if (chartInst) { chartInst.destroy(); chartInst = null; }
        });
    })();
    </script>
    @endpush
</x-layouts.app>
