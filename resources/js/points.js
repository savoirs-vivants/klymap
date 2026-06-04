const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/* ══════════════════ State ══════════════════ */
let pendingPoint    = null;   // { latlng, popup }
let editingPoint    = null;
let pointMesures    = [];
let temoinMesures   = [];
let excludedIdx     = new Set();
let icuResult       = null;
let chartTemp       = null;
let chartIcu        = null;
let pointsOnMap     = {};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/* ══════════════════ ICU Colors ══════════════════ */
function icuColor(v) {
    if (v === null || v === undefined) return '#94a3b8';
    if (v < 0.5)  return '#1e3a8a';
    if (v < 1.0)  return '#3b82f6';
    if (v < 1.5)  return '#f472b6';
    if (v < 2.0)  return '#f97316';
    return '#ef4444';
}

/* ══════════════════ Map click → position confirm ══════════════════ */
function onMapClickPoint(latlng) {
    if (!window._klymapInstance) return;

    if (pendingPoint?.popup) {
        window._klymapInstance.closePopup(pendingPoint.popup);
    }

    const popup = window.L.popup({ closeButton: false, autoClose: false, closeOnClick: false, className: 'point-confirm-popup' })
        .setLatLng(latlng)
        .setContent(`
            <div style="min-width:200px;font-family:'Space Grotesk',sans-serif;padding:4px">
                <p style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 4px">📍 Placer ici ?</p>
                <p style="font-size:11px;color:#64748b;margin:0 0 12px">${latlng.lat.toFixed(5)}, ${latlng.lng.toFixed(5)}</p>
                <div style="display:flex;gap:8px">
                    <button onclick="window.cancelPointPosition()" style="flex:1;padding:6px 0;font-size:12px;font-weight:600;color:#475569;background:#f1f5f9;border:none;border-radius:8px;cursor:pointer">Annuler</button>
                    <button onclick="window.confirmPointPosition()" style="flex:1;padding:6px 0;font-size:12px;font-weight:600;color:#fff;background:#0f172a;border:none;border-radius:8px;cursor:pointer">Valider</button>
                </div>
            </div>
        `)
        .openOn(window._klymapInstance);

    pendingPoint = { latlng, popup };
}

window.cancelPointPosition = function () {
    if (pendingPoint?.popup) window._klymapInstance.closePopup(pendingPoint.popup);
    pendingPoint = null;
};

window.confirmPointPosition = function () {
    if (pendingPoint?.popup) window._klymapInstance.closePopup(pendingPoint.popup);
    openPointModal(null);
};

/* ══════════════════ Modal open / close ══════════════════ */
window.openPointModal = function (point = null) {
    editingPoint  = point;
    pointMesures  = [];
    temoinMesures = [];
    excludedIdx   = new Set();
    icuResult     = null;

    const modal = document.getElementById('modal-point');
    if (!modal) return;

    const isAuth = document.body.dataset.auth === '1';

    document.getElementById('point-name').value       = point?.name ?? '';
    document.getElementById('point-file-input').value = '';
    document.getElementById('modal-point-title').textContent = point ? 'Détail du point de mesure' : 'Nouveau point de mesure';

    ['point-alerts-section','point-chart-temp-section','point-icu-section'].forEach((id) => {
        document.getElementById(id)?.classList.add('hidden');
    });

    const deleteBtn    = document.getElementById('point-delete-btn');
    const saveBtn      = document.getElementById('point-save-btn');
    const nameInput    = document.getElementById('point-name');
    const temoinSelect = document.getElementById('point-temoin-select');
    const fileSection  = document.getElementById('point-file-label')?.parentElement;
    const recalcBtn    = document.getElementById('btn-recalculate');

    if (!isAuth) {
        if (deleteBtn) deleteBtn.classList.replace('flex', 'hidden');
        if (saveBtn) saveBtn.classList.add('hidden');
        if (nameInput) nameInput.disabled = true;
        if (temoinSelect) temoinSelect.disabled = true;
        if (fileSection) fileSection.classList.add('hidden');
        if (recalcBtn) recalcBtn.classList.add('hidden');
    } else {
        if (deleteBtn) {
            deleteBtn.classList.toggle('hidden', !point);
            deleteBtn.classList.toggle('flex', !!point);
        }
        if (saveBtn) {
            saveBtn.classList.remove('hidden');
            saveBtn.disabled = !point;
            saveBtn.textContent = point ? 'Mettre à jour' : 'Valider';
        }
        if (nameInput) nameInput.disabled = false;
        if (temoinSelect) temoinSelect.disabled = false;
        if (fileSection) fileSection.classList.remove('hidden');
        if (recalcBtn) recalcBtn.classList.remove('hidden');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    loadTemoins().then(() => {
        if (point?.temoin_id) {
            document.getElementById('point-temoin-select').value = point.temoin_id;
        }
        if (point) loadPointDetail(point.id);
    });
};

window.closePointModal = function () {
    document.getElementById('modal-point')?.classList.replace('flex', 'hidden');
    if (chartTemp) { chartTemp.destroy(); chartTemp = null; }
    if (chartIcu)  { chartIcu.destroy();  chartIcu  = null; }
    editingPoint = null;
    pointMesures = [];
};

/* ══════════════════ Load témoins into dropdown ══════════════════ */
async function loadTemoins() {
    const sel = document.getElementById('point-temoin-select');
    if (!sel) return;
    try {
        const list = await (await fetch('/api/capteur-temoins')).json();
        sel.innerHTML = '<option value="">— Sélectionner —</option>' +
            list.map((t) => `<option value="${t.id}">${t.name}</option>`).join('');
    } catch {}
}

/* ══════════════════ Load point detail (edit mode) ══════════════════ */
async function loadPointDetail(id) {
    try {
        const data = await (await fetch(`/api/capteur-points/${id}`)).json();
        pointMesures = data.mesures ?? [];

        const temoinId = data.temoin_id;
        if (temoinId) {
            const t = await (await fetch(`/api/capteur-temoins/${temoinId}/mesures`)).json();
            temoinMesures = t.mesures ?? [];
        }

        if (pointMesures.length) await runAnalysis();
    } catch {}
}

/* ══════════════════ File parsing ══════════════════ */
function parseTxtFile(text) {
    const result = [];
    text.trim().split('\n').forEach((line, i) => {
        const m = line.match(
            /^\d+\s+(\d{4}\/\d{1,2}\/\d{1,2})\s+(\d{2}:\d{2}:\d{2}).*sht_temp=([\d.]+).*sht_hum=([\d.]+).*tmp_temp=([\d.]+)/
        );
        if (!m) return;
        const [, date, time, sht_temp, sht_hum, tmp_temp] = m;
        const [y, mo, d] = date.split('/');
        result.push({
            _idx:          i,
            enregistre_le: `${y}-${mo.padStart(2,'0')}-${d.padStart(2,'0')} ${time}`,
            sht_temp:      parseFloat(sht_temp),
            sht_hum:       parseFloat(sht_hum),
            tmp_temp:      parseFloat(tmp_temp),
            excluded:      false,
        });
    });
    return result;
}

/* ══════════════════ Flags detection — uniquement 2h–8h ══════════════════ */
function isNightWindow(enregistre_le) {
    const h = new Date(enregistre_le.replace(' ', 'T')).getHours();
    return h >= 2 && h < 8;
}

function detectFlags(mesures) {
    const night        = mesures.filter((m) => isNightWindow(m.enregistre_le));
    const flagHum      = night.filter((m) => m.sht_hum > 95);
    const flagTempDiff = night.filter((m) => Math.abs(m.sht_temp - m.tmp_temp) > 3);
    return { flagHum, flagTempDiff };
}

function renderAlerts(mesures) {
    const { flagHum, flagTempDiff } = detectFlags(mesures);
    const section    = document.getElementById('point-alerts-section');
    const allFlagged = [...new Map([...flagHum, ...flagTempDiff].map((m) => [m._idx, m])).values()];

    const alertHum  = document.getElementById('alert-hum');
    const alertTemp = document.getElementById('alert-temp-diff');

    if (flagHum.length) {
        document.getElementById('alert-hum-text').textContent =
            `${flagHum.length} mesure(s) nocturne(s) (2h–8h) avec humidité > 95 % — ces nuits seront automatiquement exclues du calcul ICU.`;
        alertHum.classList.remove('hidden');
        alertHum.classList.add('flex');
    } else {
        alertHum.classList.add('hidden');
        alertHum.classList.remove('flex');
    }

    if (flagTempDiff.length) {
        document.getElementById('alert-temp-diff-text').textContent =
            `${flagTempDiff.length} mesure(s) nocturne(s) avec un écart sht_temp / tmp_temp > 3 °C — vérification recommandée.`;
        alertTemp.classList.remove('hidden');
        alertTemp.classList.add('flex');
    } else {
        alertTemp.classList.add('hidden');
        alertTemp.classList.remove('flex');
    }

    if (allFlagged.length) {
        renderFlaggedTable(allFlagged);
        section.classList.remove('hidden');
    } else if (flagHum.length || flagTempDiff.length) {
        section.classList.remove('hidden');
    } else {
        section.classList.add('hidden');
    }
}

function renderFlaggedTable(flagged) {
    const wrapper = document.getElementById('flagged-table-wrapper');
    const tbody   = document.getElementById('flagged-tbody');
    wrapper.classList.remove('hidden');
    tbody.innerHTML = '';

    flagged.forEach((m) => {
        const humFlag  = m.sht_hum > 95;
        const diffFlag = Math.abs(m.sht_temp - m.tmp_temp) > 3;
        const reason   = [humFlag && 'Hum. > 95%', diffFlag && 'Écart temp > 3°C'].filter(Boolean).join(', ');
        const tr       = document.createElement('tr');
        tr.className   = 'hover:bg-slate-50';
        const checked  = !excludedIdx.has(m._idx);
        tr.innerHTML = `
            <td class="px-3 py-2"><input type="checkbox" class="flag-check accent-teal-600 cursor-pointer" data-idx="${m._idx}" ${checked ? 'checked' : ''}></td>
            <td class="px-3 py-2 text-slate-700 whitespace-nowrap">${m.enregistre_le}</td>
            <td class="px-3 py-2 text-right text-red-600 font-medium">${m.sht_temp}</td>
            <td class="px-3 py-2 text-right text-amber-600 font-medium">${m.sht_hum}</td>
            <td class="px-3 py-2 text-right text-slate-600">${m.tmp_temp}</td>
            <td class="px-3 py-2 text-orange-600 font-medium text-xs">${reason}</td>
        `;
        tbody.appendChild(tr);
    });
}

/* ══════════════════ Diagnostic témoin ══════════════════ */
function showTemoinStatus(count) {
    let badge = document.getElementById('temoin-status-badge');
    if (!badge) {
        badge = document.createElement('p');
        badge.id = 'temoin-status-badge';
        badge.className = 'text-xs mt-1';
        document.getElementById('point-temoin-select')?.parentElement?.appendChild(badge);
    }
    if (count === 0) {
        badge.className = 'text-xs mt-1 text-red-500 font-medium';
        badge.textContent = '⚠ Ce capteur témoin n\'a aucune mesure enregistrée — le calcul ICU est impossible.';
    } else {
        badge.className = 'text-xs mt-1 text-teal-600';
        badge.textContent = `✓ ${count} mesure(s) témoin chargée(s)`;
    }
}

/* ══════════════════ ICU Calculation ══════════════════ */
function groupByNight(mesures) {
    const groups = {};
    mesures.forEach((m) => {
        const dt = new Date(m.enregistre_le.replace(' ', 'T'));
        const h  = dt.getHours();
        if (h >= 2 && h < 8) {
            const key = m.enregistre_le.slice(0, 10);
            (groups[key] = groups[key] || []).push(m);
        }
    });
    return groups;
}

function avg(arr) { return arr.reduce((s, v) => s + v, 0) / arr.length; }
function stdDev(arr) {
    const m = avg(arr);
    return Math.sqrt(arr.reduce((s, v) => s + (v - m) ** 2, 0) / arr.length);
}

function calculateICU(pointData, temoinData, excluded) {
    if (!temoinData.length) {
        return { error: 'no_temoin', results: [], excludedNights: [], globalICU: null, globalStd: null };
    }

    const validPoint  = pointData.filter((m) => !excluded.has(m._idx ?? m.id));
    const nightPoint  = validPoint.filter((m) => isNightWindow(m.enregistre_le));
    const nightTemoin = temoinData.filter((m) => isNightWindow(m.enregistre_le));

    if (!nightPoint.length) {
        return { error: 'no_night_point', results: [], excludedNights: [], globalICU: null, globalStd: null };
    }
    if (!nightTemoin.length) {
        return { error: 'no_night_temoin', results: [], excludedNights: [], globalICU: null, globalStd: null };
    }

    const pointNights  = groupByNight(validPoint);
    const temoinNights = groupByNight(temoinData);

    const nights = [...new Set([...Object.keys(pointNights), ...Object.keys(temoinNights)])].sort();
    const results        = [];
    const excludedNights = [];

    for (const night of nights) {
        const pN = pointNights[night]  || [];
        const tN = temoinNights[night] || [];

        if (!pN.length && !tN.length) continue;
        if (!pN.length) { excludedNights.push({ night, reason: 'Pas de mesure capteur urbain cette nuit' }); continue; }
        if (!tN.length) { excludedNights.push({ night, reason: 'Pas de mesure témoin cette nuit' });        continue; }

        const maxHumP = Math.max(...pN.map((m) => m.sht_hum));
        if (maxHumP > 95) {
            excludedNights.push({ night, reason: `Humidité capteur urbain > 95 % (max: ${maxHumP} %)` });
            continue;
        }
        if (pN.length < 3 || tN.length < 3) {
            excludedNights.push({ night, reason: `< 3 mesures disponibles (urbain: ${pN.length}, témoin: ${tN.length})` });
            continue;
        }

        const p3   = [...pN].sort((a, b) => a.sht_temp - b.sht_temp).slice(0, 3);
        const t3   = [...tN].sort((a, b) => a.sht_temp - b.sht_temp).slice(0, 3);
        const pAvg = avg(p3.map((m) => m.sht_temp));
        const tAvg = avg(t3.map((m) => m.sht_temp));
        const diff = pAvg - tAvg;

        results.push({
            night,
            diff: Math.round(diff * 100) / 100,
            pAvg: Math.round(pAvg * 100) / 100,
            tAvg: Math.round(tAvg * 100) / 100,
            p3, t3,
        });
    }

    if (!results.length) {
        return { error: 'no_valid_nights', results: [], excludedNights, globalICU: null, globalStd: null };
    }

    const diffs     = results.map((r) => r.diff);
    const globalICU = Math.round(avg(diffs) * 100) / 100;
    const globalStd = Math.round(stdDev(diffs) * 100) / 100;

    return { results, excludedNights, globalICU, globalStd, valid: globalStd <= 0.5 };
}

/* ══════════════════ Charts ══════════════════ */
function renderTempChart(pointData, temoinData) {
    const allTimes  = [...new Set([...pointData, ...temoinData].map((m) => m.enregistre_le))].sort();
    const pMap      = Object.fromEntries(pointData.map((m) => [m.enregistre_le, m.sht_temp]));
    const tMap      = Object.fromEntries(temoinData.map((m) => [m.enregistre_le, m.sht_temp]));

    const labels    = allTimes.map((t) => t.slice(0, 16).replace('T', ' '));

    if (chartTemp) chartTemp.destroy();

    chartTemp = new Chart(document.getElementById('chart-temperature'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Capteur urbain',
                    data:  allTimes.map((t) => pMap[t] ?? null),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.06)',
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2,
                    spanGaps: true,
                    fill: true,
                },
                {
                    label: 'Capteur témoin',
                    data:  allTimes.map((t) => tMap[t] ?? null),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.06)',
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2,
                    spanGaps: true,
                    fill: true,
                },
            ],
        },
        options: {
            animation: { duration: 600 },
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    labels: { font: { size: 11 }, usePointStyle: true, pointStyleWidth: 20 },
                },
                tooltip: { mode: 'index', intersect: false },
            },
            scales: {
                x: { ticks: { maxTicksLimit: 8, font: { size: 10 } }, grid: { color: '#f1f5f9' } },
                y: { ticks: { font: { size: 10 } }, grid: { color: '#f1f5f9' } },
            },
        },
    });
}

function renderICUChart(results, globalICU, globalStd) {
    if (chartIcu) chartIcu.destroy();

    const labels  = results.map((r) => r.night);
    const upper   = results.map(() => Math.round((globalICU + globalStd) * 100) / 100);
    const lower   = results.map(() => Math.round((globalICU - globalStd) * 100) / 100);

    chartIcu = new Chart(document.getElementById('chart-icu'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: `Bande +σ (${globalICU + globalStd > 0 ? '+' : ''}${Math.round((globalICU + globalStd)*100)/100} °C)`,
                    data:  upper,
                    type:  'line',
                    borderColor: 'transparent',
                    backgroundColor: 'rgba(15,23,42,0.07)',
                    pointRadius: 0,
                    fill: '+1',
                    tension: 0,
                    order: 1,
                },
                {
                    label: `Bande −σ (${Math.round((globalICU - globalStd)*100)/100} °C)`,
                    data:  lower,
                    type:  'line',
                    borderColor: 'transparent',
                    backgroundColor: 'transparent',
                    pointRadius: 0,
                    fill: false,
                    tension: 0,
                    order: 1,
                },
                {
                    label: `ICU moyen : ${globalICU} °C`,
                    data:  results.map(() => globalICU),
                    type:  'line',
                    borderColor: '#0f172a',
                    borderWidth: 2.5,
                    borderDash: [6, 4],
                    pointRadius: 0,
                    fill: false,
                    order: 2,
                },
                {
                    label: 'ICU par nuit (°C)',
                    data:  results.map((r) => r.diff),
                    type:  'bar',
                    backgroundColor: results.map((r) => icuColor(r.diff) + 'bb'),
                    borderColor:     results.map((r) => icuColor(r.diff)),
                    borderWidth: 1.5,
                    borderRadius: 6,
                    order: 3,
                },
            ],
        },
        options: {
            animation: { duration: 600 },
            responsive: true,
            plugins: {
                legend: { labels: { font: { size: 10 }, filter: (item) => item.text.startsWith('ICU') } },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: (ctx) => {
                            if (ctx.dataset.label.startsWith('Bande')) return null;
                            return `${ctx.dataset.label}: ${ctx.parsed.y} °C`;
                        },
                        afterBody: (items) => {
                            if (items.length) return [`Écart-type : ±${globalStd} °C`];
                        },
                    },
                },
            },
            scales: {
                x: { ticks: { font: { size: 10 } }, grid: { color: '#f1f5f9' } },
                y: {
                    title: { display: true, text: '°C', font: { size: 10 } },
                    ticks: { font: { size: 10 } },
                    grid:  { color: '#f1f5f9' },
                },
            },
        },
    });
}

/* ══════════════════ Render ICU results ══════════════════ */
function renderICUSummary(result) {
    document.getElementById('icu-global-val').textContent = result.globalICU;
    document.getElementById('icu-stddev-val').textContent = result.globalStd;
    document.getElementById('icu-nights-val').textContent = result.results.length;

    const rel = document.getElementById('icu-reliability');
    rel.classList.remove('hidden', 'bg-green-50', 'border-green-200', 'text-green-800', 'bg-red-50', 'border-red-200', 'text-red-800', 'flex');
    rel.classList.add('flex');

    if (result.valid) {
        rel.classList.add('bg-green-50', 'border-green-200', 'text-green-800');
        document.getElementById('icu-rel-icon').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
        document.getElementById('icu-rel-text').textContent = `Donnée fiable — écart-type de ${result.globalStd} °C (≤ 0,5 °C).`;
    } else {
        rel.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
        document.getElementById('icu-rel-icon').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>';
        document.getElementById('icu-rel-text').textContent = `Vérification manuelle recommandée — écart-type de ${result.globalStd} °C (> 0,5 °C). Contrôlez les mesures suspectes ci-dessus.`;
    }
}

function renderNightTable(result) {
    const tbody = document.getElementById('night-tbody');
    tbody.innerHTML = '';

    result.results.forEach((r, i) => {
        const tr      = document.createElement('tr');
        tr.className  = i % 2 === 1 ? 'bg-slate-50' : '';
        const dotColor = icuColor(r.diff);
        tr.innerHTML = `
            <td class="px-3 py-2 text-slate-700 whitespace-nowrap font-medium">${r.night}</td>
            <td class="px-3 py-2 text-right text-red-600 font-medium">${r.pAvg} °C</td>
            <td class="px-3 py-2 text-right text-emerald-600 font-medium">${r.tAvg} °C</td>
            <td class="px-3 py-2 text-right font-bold" style="color:${dotColor}">${r.diff > 0 ? '+' : ''}${r.diff} °C</td>
            <td class="px-3 py-2"><span class="inline-block w-2.5 h-2.5 rounded-full" style="background:${dotColor}"></span></td>
        `;
        tbody.appendChild(tr);
    });

    result.excludedNights.forEach((n) => {
        const tr = document.createElement('tr');
        tr.className = 'opacity-50';
        tr.innerHTML = `
            <td class="px-3 py-2 text-slate-500 italic">${n.night}</td>
            <td colspan="3" class="px-3 py-2 text-slate-400 italic text-xs">${n.reason}</td>
            <td></td>
        `;
        tbody.appendChild(tr);
    });
}

/* ══════════════════ Progress indicator ══════════════════ */
function setProgress(text) {
    const el = document.getElementById('point-progress');
    if (text) {
        document.getElementById('point-progress-text').textContent = text;
        el.classList.remove('hidden');
        el.classList.add('flex');
    } else {
        el.classList.add('hidden');
        el.classList.remove('flex');
    }
}

/* ══════════════════ Full analysis pipeline ══════════════════ */
async function runAnalysis() {
    if (!pointMesures.length) return;

    setProgress('Détection des alertes…');
    await sleep(30);
    renderAlerts(pointMesures);

    setProgress('Génération du graphique thermique…');
    await sleep(50);

    if (temoinMesures.length) {
        document.getElementById('point-chart-temp-section').classList.remove('hidden');
        renderTempChart(pointMesures, temoinMesures);
    }

    if (temoinMesures.length) {
        setProgress('Calcul de l\'ICU…');
        await sleep(80);

        icuResult = calculateICU(pointMesures, temoinMesures, excludedIdx);

        document.getElementById('point-icu-section').classList.remove('hidden');

        if (icuResult) {
            renderICUSummary(icuResult);
            renderICUChart(icuResult.results, icuResult.globalICU, icuResult.globalStd);
            renderNightTable(icuResult);
        } else {
            document.getElementById('icu-global-val').textContent = '—';
            document.getElementById('icu-stddev-val').textContent = '—';
            document.getElementById('icu-nights-val').textContent = '0';
            document.getElementById('icu-reliability').classList.add('hidden');

            const errorMessages = {
                no_temoin:       'Le capteur témoin sélectionné n\'a aucune mesure en base. Vérifiez qu\'il a bien été importé.',
                no_night_point:  'Aucune mesure du capteur urbain entre 2h et 8h. Vérifiez la plage horaire de vos données.',
                no_night_temoin: 'Le capteur témoin n\'a pas de mesures entre 2h et 8h sur la même période.',
                no_valid_nights: `Toutes les nuits ont été exclues (humidité > 95 % ou données insuffisantes).`,
            };
            const reason = errorMessages[icuResult?.error] ?? 'Calcul ICU impossible.';

            const excludedInfo = icuResult?.excludedNights?.length
                ? `<p class="text-[10px] text-slate-400 mt-2">Nuits analysées :</p>` + icuResult.excludedNights.map(
                    (n) => `<p class="text-[10px] text-slate-400">• ${n.night} — ${n.reason}</p>`
                  ).join('')
                : '';

            document.getElementById('night-tbody').innerHTML =
                `<tr><td colspan="5" class="px-4 py-4">
                    <p class="text-sm font-medium text-red-600 mb-1">⚠ ${reason}</p>
                    ${excludedInfo}
                </td></tr>`;
        }
    }

    setProgress(null);

    const saveBtn = document.getElementById('point-save-btn');
    saveBtn.disabled = false;
}

/* ══════════════════ Save to backend ══════════════════ */
async function savePoint() {
    const name     = document.getElementById('point-name').value.trim();
    const temoinId = document.getElementById('point-temoin-select').value;

    if (!name)     { document.getElementById('point-name').focus(); return; }
    if (!temoinId) { document.getElementById('point-temoin-select').focus(); return; }
    if (!editingPoint && !pointMesures.length) { alert('Importez un fichier de données.'); return; }

    const saveBtn = document.getElementById('point-save-btn');
    saveBtn.disabled    = true;
    saveBtn.textContent = 'Enregistrement…';

    try {
        const mesuresWithExcluded = pointMesures.map((m) => ({
            ...m,
            excluded: excludedIdx.has(m._idx ?? m.id),
        }));

        const isEdit = !!editingPoint;
        const url    = isEdit ? `/api/capteur-points/${editingPoint.id}` : '/api/capteur-points';
        const body   = isEdit
            ? {
                name,
                icu_value: icuResult?.globalICU ?? editingPoint.icu_value,
                std_dev:   icuResult?.globalStd  ?? editingPoint.std_dev,
                mesures:   mesuresWithExcluded,
                excluded:  [...excludedIdx],
              }
            : {
                name,
                capteur_temoin_id: parseInt(temoinId),
                lat:      pendingPoint?.latlng?.lat,
                lng:      pendingPoint?.latlng?.lng,
                mesures:  mesuresWithExcluded,
                icu_value: icuResult?.globalICU ?? null,
                std_dev:   icuResult?.globalStd  ?? null,
              };

        const res = await fetch(url, {
            method:  isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body:    JSON.stringify(body),
        });
        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        if (!isEdit) {
            addPointMarker(data);
        } else {
            pointsOnMap[editingPoint.id]?.setIcon(buildPointIcon(data));
            pointsOnMap[editingPoint.id]?.setPopupContent(pointPopupContent(data));
        }

        pendingPoint = null;
        window.closePointModal();
    } catch (err) {
        console.error(err);
        alert("Erreur lors de l'enregistrement.");
    } finally {
        saveBtn.disabled    = false;
        saveBtn.textContent = editingPoint ? 'Mettre à jour' : 'Valider';
    }
}

/* ══════════════════ Delete ══════════════════ */
window.deletePoint = async function (id) {
    if (!confirm('Supprimer ce point de mesure et toutes ses données ?')) return;
    try {
        await fetch(`/api/capteur-points/${id}`, {
            method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
        });
        pointsOnMap[id]?.remove();
        delete pointsOnMap[id];
        window.closePointModal();
    } catch { alert("Erreur lors de la suppression."); }
};

/* ══════════════════ Map markers ══════════════════ */
function buildPointIcon(p) {
    const color = icuColor(p.icu_value);
    const isAuth = document.body.dataset.auth === '1';
    const needsVerification = isAuth && p.std_dev !== null && p.std_dev > 0.5;
    let badge = '';
    if (needsVerification) {
        badge = `
            <div style="position:absolute; top:-2px; right:-2px; width:12px; height:12px; background:#ef4444; border:2px solid #fff; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.4); z-index:10;"></div>
        `;
    }

    return window.L.divIcon({
        className: '',
        html: `
            <div style="position:relative; width:28px; height:28px;">
                <div style="width:100%; height:100%; border-radius:50%; background:${color}; border:4px solid #fff; box-shadow:0 4px 12px rgba(0,0,0,0.4);"></div>
                ${badge}
            </div>
        `,
        iconSize:   [28, 28],
        iconAnchor: [14, 14],
    });
}

function pointPopupContent(p) {
    const color = icuColor(p.icu_value);
    const icu   = p.icu_value !== null ? `${p.icu_value} °C` : '—';
    const data  = JSON.stringify(p).replace(/"/g, '&quot;');
    const isAuth = document.body.dataset.auth === '1';
    let warningHtml = '';
    if (isAuth && p.std_dev !== null && p.std_dev > 0.5) {
        warningHtml = `
            <p style="font-size:10px; font-weight:600; color:#ef4444; margin:0 0 8px; display:flex; align-items:start; gap:4px; line-height:1.2;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0; margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                Vérification requise (σ = ${p.std_dev})
            </p>
        `;
    }

    return `
        <div style="min-width:180px;font-family:'Space Grotesk',sans-serif">
            <p style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px">🌡 ${p.name}</p>
            <p style="font-size:11px;color:#64748b;margin:0 0 2px">Témoin : ${p.temoin_name}</p>
            <p style="font-size:11px;color:#64748b;margin:0 0 10px">Posé par : ${p.user_name}</p>
            <p style="font-size:16px;font-weight:900;color:${color};margin:0 0 4px">ICU : ${icu}</p>
            ${warningHtml}
            <button onclick="window.openPointModal(JSON.parse(this.dataset.p))" data-p="${data}"
                style="width:100%;padding:6px 0;font-size:12px;font-weight:600;color:#fff;background:#0f172a;border:none;border-radius:8px;cursor:pointer">
                Détail
            </button>
        </div>`;
}

function addPointMarker(p) {
    if (!window._klymapInstance) return;
    const marker = window.L.marker([p.lat, p.lng], { icon: buildPointIcon(p) })
        .addTo(window._klymapInstance)
        .bindPopup(pointPopupContent(p));
    marker.pointData = p;
    pointsOnMap[p.id] = marker;
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('point-file-input')?.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const temoinId = document.getElementById('point-temoin-select').value;
        if (!temoinId) {
            alert('Sélectionnez d\'abord un capteur témoin.');
            e.target.value = '';
            return;
        }

        document.getElementById('point-file-cta').textContent = `${file.name} — chargement…`;

        const text = await file.text();
        pointMesures = parseTxtFile(text);

        if (!pointMesures.length) { alert('Aucune donnée reconnue.'); return; }

        document.getElementById('point-file-cta').textContent = `${file.name} — ${pointMesures.length} mesures`;

        setProgress('Chargement des données témoin…');
        try {
            const res = await fetch(`/api/capteur-temoins/${temoinId}/mesures`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const t   = await res.json();
            temoinMesures = t.mesures ?? [];
        } catch (e) {
            console.error('Erreur chargement témoin:', e);
            temoinMesures = [];
        }

        showTemoinStatus(temoinMesures.length);
        await runAnalysis();
    });

    document.getElementById('point-temoin-select')?.addEventListener('change', async () => {
        if (!pointMesures.length) return;
        const temoinId = document.getElementById('point-temoin-select').value;
        if (!temoinId) return;
        setProgress('Chargement des données témoin…');
        try {
            const res = await fetch(`/api/capteur-temoins/${temoinId}/mesures`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const t   = await res.json();
            temoinMesures = t.mesures ?? [];
        } catch (e) {
            console.error('Erreur chargement témoin:', e);
            temoinMesures = [];
        }
        showTemoinStatus(temoinMesures.length);
        await runAnalysis();
    });

    document.getElementById('btn-recalculate')?.addEventListener('click', async () => {
        excludedIdx = new Set();
        document.querySelectorAll('.flag-check').forEach((cb) => {
            if (!cb.checked) excludedIdx.add(parseInt(cb.dataset.idx));
        });
        if (chartIcu) { chartIcu.destroy(); chartIcu = null; }
        await runAnalysis();
    });

    document.getElementById('point-save-btn')?.addEventListener('click', savePoint);

    document.getElementById('point-delete-btn')?.addEventListener('click', () => {
        if (editingPoint) window.deletePoint(editingPoint.id);
    });

    document.getElementById('modal-point-backdrop')?.addEventListener('click', () => {
        window.closePointModal();
    });
});

document.addEventListener('klymap:ready', () => {
    if (!window._klymapInstance) return;
    const isAuth = document.body.dataset.auth === '1';
    window._klymapInstance.on('click', (e) => {
        if (window._placementMode) return;

        if (isAuth) {
            onMapClickPoint(e.latlng);
        }
    });

    fetch('/api/capteur-points')
        .then((r) => r.json())
        .then((points) => points.forEach(addPointMarker))
        .catch(() => {});
});

window.toggleMapFilter = function(btnElement, min, max) {
    const allButtons = document.querySelectorAll('.legend-filter');
    const resetBtn = document.getElementById('btn-reset-filters');

    if (btnElement.classList.contains('ring-1')) {
        window.resetMapFilters();
        return;
    }

    if (resetBtn) resetBtn.classList.remove('hidden');

    allButtons.forEach(btn => {
        btn.classList.remove('bg-teal-900/40', 'ring-1', 'ring-white/50');
        btn.classList.add('opacity-30');
        const dot = btn.querySelector('div');
        if (dot) dot.classList.remove('scale-125');
    });

    btnElement.classList.remove('opacity-30');
    btnElement.classList.add('bg-teal-900/40', 'ring-1', 'ring-white/50', 'opacity-100');
    const dot = btnElement.querySelector('div');
    if (dot) dot.classList.add('scale-125');

    Object.values(pointsOnMap).forEach(marker => {
        if (!marker.pointData) return;

        const icu = marker.pointData.icu_value;
        let shouldShow = false;

        if (min === 'temoin') {
            shouldShow = (icu === null || icu === undefined);
        } else {
            if (icu !== null && icu >= min && icu < max) {
                shouldShow = true;
            }
        }

        if (shouldShow) {
            if (!window._klymapInstance.hasLayer(marker)) {
                window._klymapInstance.addLayer(marker);
            }
        } else {
            if (window._klymapInstance.hasLayer(marker)) {
                window._klymapInstance.removeLayer(marker);
            }
        }
    });
};

window.resetMapFilters = function() {
    const allButtons = document.querySelectorAll('.legend-filter');
    const resetBtn = document.getElementById('btn-reset-filters');

    if (resetBtn) resetBtn.classList.add('hidden');

    allButtons.forEach(btn => {
        btn.classList.remove('bg-teal-900/40', 'ring-1', 'ring-white/50', 'opacity-30');
        btn.classList.add('opacity-100');
        const dot = btn.querySelector('div');
        if (dot) dot.classList.remove('scale-125');
    });

    Object.values(pointsOnMap).forEach(marker => {
        if (!window._klymapInstance.hasLayer(marker)) {
            window._klymapInstance.addLayer(marker);
        }
    });
};
