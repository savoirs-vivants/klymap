const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function openLightbox(src) {
    const lb  = document.getElementById('img-lightbox');
    const img = document.getElementById('img-lightbox-src');
    if (!lb || !img || !src) return;
    img.src = src;
    lb.classList.remove('hidden');
}

// Fermeture lightbox avec Échap
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') document.getElementById('img-lightbox')?.classList.add('hidden');
});

function isOwner(point) {
    const userId      = parseInt(document.body.dataset.userId || '0');
    const pJson       = document.body.dataset.participantJson;
    const participant = pJson ? JSON.parse(pJson) : null;

    if (userId && point.user_id && point.user_id === userId) return true;
    if (participant?.id && point.participant_id && point.participant_id === participant.id) return true;
    return false;
}

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
let nightOverrides  = [];   // [{ night: 'YYYY-MM-DD', pAvg, tAvg }] — corrections manuelles des nuits ICU

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
    editingPoint   = point;
    pointMesures   = [];
    temoinMesures  = [];
    excludedIdx    = new Set();
    icuResult      = null;
    nightOverrides = [];

    const modal = document.getElementById('modal-point');
    if (!modal) return;

    const isAuth  = document.body.dataset.auth === '1';
    const isAdmin = document.body.dataset.admin === '1';

    const nameInput = document.getElementById('point-name');
    if (nameInput) nameInput.value = point?.name ?? '';

    const dateInput = document.getElementById('point-date');
    if (dateInput) dateInput.value = point?.date ?? '';

    // Image preview (zone upload + miniature dans l'en-tête)
    const imgPreview = document.getElementById('point-image-preview');
    const imgThumb   = document.getElementById('modal-point-image-thumb');
    const imgCta     = document.getElementById('point-image-cta');
    if (point?.image_url) {
        if (imgPreview) { imgPreview.src = point.image_url; imgPreview.classList.remove('hidden'); }
        if (imgThumb)   { imgThumb.src   = point.image_url; imgThumb.classList.remove('hidden'); }
        if (imgCta) imgCta.textContent = 'Changer la photo';
    } else {
        if (imgPreview) { imgPreview.src = ''; imgPreview.classList.add('hidden'); }
        if (imgThumb)   { imgThumb.src   = ''; imgThumb.classList.add('hidden'); }
        if (imgCta) imgCta.textContent = 'Ajouter une photo';
    }

    // Rendre les images cliquables → lightbox
    [imgPreview, imgThumb].forEach((el) => {
        if (!el) return;
        el.style.cursor = 'zoom-in';
        el.onclick = () => openLightbox(el.src);
    });

    // Synchroniser le thumb header quand l'utilisateur choisit une nouvelle image
    const imgInput = document.getElementById('point-image-input');
    if (imgInput) {
        imgInput.addEventListener('change', () => {
            const file = imgInput.files[0];
            if (!file) return;
            const r = new FileReader();
            r.onload = (e) => {
                if (imgThumb) {
                    imgThumb.src = e.target.result;
                    imgThumb.classList.remove('hidden');
                    imgThumb.onclick = () => openLightbox(imgThumb.src);
                }
            };
            r.readAsDataURL(file);
        }, { once: true });
    }

    const fileInput = document.getElementById('point-file-input');
    if (fileInput) fileInput.value = '';

    const titleEl = document.getElementById('modal-point-title');
    if (titleEl) titleEl.textContent = point ? 'Détail du point de mesure' : 'Nouveau point de mesure';

    ['point-alerts-section','point-chart-temp-section','point-icu-section'].forEach((id) => {
        document.getElementById(id)?.classList.add('hidden');
    });

    const deleteBtn    = document.getElementById('point-delete-btn');
    const saveBtn      = document.getElementById('point-save-btn');
    const temoinSelect = document.getElementById('point-temoin-select');
    const fileSection  = document.getElementById('point-file-section');

    const canEdit = isAuth && (!point || isOwner(point) || isAdmin);

    const dateInput2  = document.getElementById('point-date');
    const imageSection = document.getElementById('point-image-section');
    const imageInput   = document.getElementById('point-image-input');

    if (!canEdit) {
        if (deleteBtn) deleteBtn.classList.replace('flex', 'hidden');
        if (saveBtn)   saveBtn.classList.add('hidden');
        if (nameInput) nameInput.disabled = true;
        if (dateInput2) dateInput2.disabled = true;
        if (temoinSelect) temoinSelect.disabled = true;
        if (fileSection) fileSection.classList.add('hidden');
        if (imageSection) imageSection.classList.add('hidden');
        document.getElementById('point-alerts-section')?.classList.add('hidden');
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
        if (dateInput2) dateInput2.disabled = false;
        if (temoinSelect) temoinSelect.disabled = false;
        if (fileSection) fileSection.classList.remove('hidden');
        if (imageSection) imageSection.classList.remove('hidden');

        // Prévisualisation locale + upload si point existant
        if (imageInput && !imageInput._listenerAdded) {
            imageInput._listenerAdded = true;
            imageInput.addEventListener('change', async () => {
                const file = imageInput.files[0];
                if (!file) return;

                // Prévisualisation locale immédiate (fonctionne aussi pour un nouveau point)
                const prev = document.getElementById('point-image-preview');
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (prev) { prev.src = e.target.result; prev.classList.remove('hidden'); }
                    const cta = document.getElementById('point-image-cta');
                    if (cta) cta.textContent = 'Changer la photo';
                };
                reader.readAsDataURL(file);

                // Upload serveur uniquement si le point existe déjà en BDD
                if (!editingPoint) return;
                const fd = new FormData();
                fd.append('image', file);
                try {
                    const r = await fetch(`/api/capteur-points/${editingPoint.id}/image`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF },
                        body: fd,
                    });
                    if (!r.ok) throw new Error();
                    const { image_url } = await r.json();
                    if (prev) prev.src = image_url;
                } catch { alert("Erreur lors de l'upload de l'image."); }
            });
        }
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    loadTemoins().then(() => {
        const selectEl = document.getElementById('point-temoin-select');
        if (point?.temoin_id && selectEl) {
            selectEl.value = point.temoin_id;
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

/* ══════════════════ Load point detail (edit/view mode) ══════════════════ */
async function loadPointDetail(id) {
    try {
        const data = await (await fetch(`/api/capteur-points/${id}`)).json();
        pointMesures = (data.mesures ?? []).map((m, i) => ({ ...m, _idx: i }));

        // Restaurer les exclusions persistées (nuits supprimées)
        excludedIdx = new Set();
        pointMesures.forEach((m) => {
            if (m.excluded) excludedIdx.add(m._idx);
        });

        // Restaurer les overrides de nuits persistés
        nightOverrides = Array.isArray(data.night_overrides) ? data.night_overrides : [];

        const temoinId = data.temoin_id;
        if (temoinId) {
            setProgress('Chargement des données témoin…');
            const t = await (await fetch(`/api/capteur-temoins/${temoinId}/mesures`)).json();
            temoinMesures = t.mesures ?? [];
            showTemoinStatus(temoinMesures.length);
        }

        if (pointMesures.length) await runAnalysis();
    } catch (e) {
        console.error('loadPointDetail error:', e);
    }
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
    if (document.body.dataset.auth !== '1') return;
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
        const ackKey = flaggedAcknowledgeKey();
        if (ackKey && localStorage.getItem(ackKey) === '1') {
            section.classList.add('hidden');
        } else {
            renderFlaggedTable(allFlagged);
            section.classList.remove('hidden');
        }
    } else if (flagHum.length || flagTempDiff.length) {
        section.classList.remove('hidden');
    } else {
        section.classList.add('hidden');
    }
}

function flaggedAcknowledgeKey() {
    return editingPoint ? `flags_ack_${editingPoint.id}` : null;
}

function renderFlaggedTable(flagged) {
    // Si l'utilisateur a déjà acquitté pour ce point, on ne montre pas le tableau
    const ackKey = flaggedAcknowledgeKey();
    if (ackKey && localStorage.getItem(ackKey) === '1') return;

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
        tr.innerHTML = `
            <td class="px-3 py-2 text-slate-700 whitespace-nowrap">${m.enregistre_le}</td>
            <td class="px-3 py-2 text-right text-red-600 font-medium">${m.sht_temp}</td>
            <td class="px-3 py-2 text-right text-amber-600 font-medium">${m.sht_hum}</td>
            <td class="px-3 py-2 text-right text-slate-600">${m.tmp_temp}</td>
            <td class="px-3 py-2 text-orange-600 font-medium text-xs">${reason}</td>
        `;
        tbody.appendChild(tr);
    });

    const ackBtn = document.getElementById('btn-acknowledge-errors');
    if (ackBtn) {
        ackBtn.onclick = () => {
            if (ackKey) localStorage.setItem(ackKey, '1');
            // Masquer toute la section 3 immédiatement
            document.getElementById('point-alerts-section')?.classList.add('hidden');
        };
    }
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

// Recalcule l'ICU en appliquant les overrides manuels sur pAvg/tAvg
function calculateICUWithOverrides() {
    const base = calculateICU(pointMesures, temoinMesures, excludedIdx);
    if (!base || !base.results?.length) return base;

    if (!nightOverrides.length) return base;

    const results = base.results.map((r) => {
        const ov = nightOverrides.find((o) => o.night === r.night);
        if (!ov) return r;
        const diff = Math.round((ov.pAvg - ov.tAvg) * 100) / 100;
        return { ...r, pAvg: ov.pAvg, tAvg: ov.tAvg, diff };
    });

    const diffs     = results.map((r) => r.diff);
    const globalICU = Math.round(avg(diffs) * 100) / 100;
    const globalStd = Math.round(stdDev(diffs) * 100) / 100;

    return { ...base, results, globalICU, globalStd, valid: globalStd <= 0.5 };
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
        document.getElementById('icu-rel-text').textContent = `Vérification manuelle recommandée — écart-type de ${result.globalStd} °C (> 0,5 °C).`;
    }
}

function renderNightTable(result) {
    const tbody   = document.getElementById('night-tbody');
    const isAuth  = document.body.dataset.auth === '1';
    const isAdmin = document.body.dataset.admin === '1';
    const canEdit = isAuth && (!editingPoint || isOwner(editingPoint) || isAdmin);
    tbody.innerHTML = '';

    result.results.forEach((r, i) => {
        const tr       = document.createElement('tr');
        tr.dataset.night = r.night;
        tr.className   = i % 2 === 1 ? 'bg-slate-50' : '';
        const dotColor = icuColor(r.diff);

        const actionCell = canEdit ? `
            <td class="px-2 py-2 whitespace-nowrap">
                <div class="flex items-center gap-1">
                    <button data-night-edit="${r.night}"
                        class="night-edit-btn px-2 py-1 text-[10px] font-semibold bg-slate-100 hover:bg-blue-100 text-slate-600 hover:text-blue-700 rounded-lg transition-colors">
                        Modifier
                    </button>
                    <button data-night-delete="${r.night}"
                        class="night-delete-btn px-2 py-1 text-[10px] font-semibold bg-slate-100 hover:bg-red-100 text-slate-600 hover:text-red-600 rounded-lg transition-colors">
                        Supprimer
                    </button>
                </div>
            </td>` : '<td></td>';

        tr.innerHTML = `
            <td class="px-3 py-2 text-slate-700 whitespace-nowrap font-medium">${r.night}</td>
            <td class="px-3 py-2 text-right text-red-600 font-medium night-pavg">${r.pAvg} °C</td>
            <td class="px-3 py-2 text-right text-emerald-600 font-medium night-tavg">${r.tAvg} °C</td>
            <td class="px-3 py-2 text-right font-bold" style="color:${dotColor}">${r.diff > 0 ? '+' : ''}${r.diff} °C</td>
            <td class="px-3 py-2"><span class="inline-block w-2.5 h-2.5 rounded-full" style="background:${dotColor}"></span></td>
            ${actionCell}
        `;
        tbody.appendChild(tr);
    });

    result.excludedNights.forEach((n) => {
        const tr = document.createElement('tr');
        tr.className = 'opacity-50';
        tr.innerHTML = `
            <td class="px-3 py-2 text-slate-500 italic">${n.night}</td>
            <td colspan="3" class="px-3 py-2 text-slate-400 italic text-xs">${n.reason}</td>
            <td></td><td></td>
        `;
        tbody.appendChild(tr);
    });

    // Boutons supprimer nuit
    tbody.querySelectorAll('.night-delete-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const night = btn.dataset.nightDelete;
            if (!confirm(`Supprimer la nuit du ${night} du calcul ICU ?`)) return;
            nightOverrides = nightOverrides.filter((o) => o.night !== night);
            // Ajouter les indices des mesures de cette nuit dans excludedIdx (utilisé par calculateICU)
            pointMesures.forEach((m) => {
                if (m.enregistre_le.slice(0, 10) === night) {
                    excludedIdx.add(m._idx ?? m.id);
                }
            });
            const newResult = calculateICU(pointMesures, temoinMesures, excludedIdx);
            if (newResult && newResult.results) {
                icuResult = newResult;
                renderICUSummary(newResult);
                renderICUChart(newResult.results, newResult.globalICU, newResult.globalStd);
                renderNightTable(newResult);
            }
        });
    });

    // Boutons modifier nuit
    tbody.querySelectorAll('.night-edit-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const night = btn.dataset.nightEdit;
            const tr    = tbody.querySelector(`tr[data-night="${night}"]`);
            if (!tr) return;
            const pCell = tr.querySelector('.night-pavg');
            const tCell = tr.querySelector('.night-tavg');
            const curP  = parseFloat(pCell.textContent);
            const curT  = parseFloat(tCell.textContent);

            // Remplace les cellules par des inputs
            pCell.innerHTML = `<input type="number" step="0.01" value="${curP}"
                class="w-20 text-right text-xs border border-slate-300 rounded px-1 py-0.5 font-mono">`;
            tCell.innerHTML = `<input type="number" step="0.01" value="${curT}"
                class="w-20 text-right text-xs border border-slate-300 rounded px-1 py-0.5 font-mono">`;

            const actionDiv = btn.closest('div');
            actionDiv.innerHTML = `
                <button class="night-save-btn px-2 py-1 text-[10px] font-semibold bg-emerald-100 hover:bg-emerald-200 text-emerald-700 rounded-lg transition-colors">OK</button>
                <button class="night-cancel-btn px-2 py-1 text-[10px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition-colors">Annuler</button>
            `;

            actionDiv.querySelector('.night-cancel-btn').addEventListener('click', () => {
                renderNightTable(icuResult);
            });

            actionDiv.querySelector('.night-save-btn').addEventListener('click', () => {
                const newP = parseFloat(pCell.querySelector('input').value);
                const newT = parseFloat(tCell.querySelector('input').value);
                if (isNaN(newP) || isNaN(newT)) return;

                // Stocker l'override en mémoire et recalculer
                nightOverrides = nightOverrides.filter((o) => o.night !== night);
                nightOverrides.push({ night, pAvg: newP, tAvg: newT });

                const newResult = calculateICUWithOverrides();
                if (newResult) {
                    icuResult = newResult;
                    renderICUSummary(newResult);
                    renderICUChart(newResult.results, newResult.globalICU, newResult.globalStd);
                    renderNightTable(newResult);
                }
            });
        });
    });
}

/* ══════════════════ Progress indicator ══════════════════ */
function setProgress(text) {
    const el   = document.getElementById('point-progress');
    const label = document.getElementById('point-progress-text');
    if (!el) return;
    if (text) {
        if (label) label.textContent = text;
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

        icuResult = calculateICUWithOverrides();

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
                `<tr><td colspan="6" class="px-4 py-4">
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
    const name     = document.getElementById('point-name')?.value.trim();
    const dateVal  = document.getElementById('point-date')?.value || null;
    const temoinId = document.getElementById('point-temoin-select')?.value;

    if (!name) { document.getElementById('point-name')?.focus(); return; }
    if (!editingPoint && !pointMesures.length) { alert('Importez un fichier de données.'); return; }
    if (!editingPoint && !temoinId) { document.getElementById('point-temoin-select')?.focus(); return; }

    const saveBtn = document.getElementById('point-save-btn');
    if (!saveBtn) return;
    saveBtn.disabled    = true;
    saveBtn.textContent = 'Enregistrement…';

    try {
        const isEdit = !!editingPoint;

        const mesuresWithExcluded = pointMesures.map((m) => ({
            enregistre_le: m.enregistre_le,
            sht_temp: m.sht_temp,
            sht_hum:  m.sht_hum,
            tmp_temp: m.tmp_temp,
            excluded: excludedIdx.has(m._idx ?? m.id),
        }));

        const url  = isEdit ? `/api/capteur-points/${editingPoint.id}` : '/api/capteur-points';
        const body = isEdit
            ? {
                name,
                date:              dateVal,
                capteur_temoin_id: temoinId ? parseInt(temoinId) : undefined,
                icu_value:       icuResult?.globalICU ?? editingPoint.icu_value,
                std_dev:         icuResult?.globalStd  ?? editingPoint.std_dev,
                night_overrides: nightOverrides.length ? nightOverrides : [],
                mesures:         mesuresWithExcluded.length ? mesuresWithExcluded : undefined,
              }
            : {
                name,
                date:              dateVal,
                capteur_temoin_id: parseInt(temoinId),
                lat:             pendingPoint?.latlng?.lat,
                lng:             pendingPoint?.latlng?.lng,
                mesures:         mesuresWithExcluded,
                icu_value:       icuResult?.globalICU ?? null,
                std_dev:         icuResult?.globalStd  ?? null,
                night_overrides: nightOverrides.length ? nightOverrides : [],
              };

        const res = await fetch(url, {
            method:  isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body:    JSON.stringify(body),
        });
        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        if (!isEdit) {
            // Upload de l'image si une a été sélectionnée (le point vient d'être créé → on a maintenant son id)
            const imageInput = document.getElementById('point-image-input');
            if (imageInput?.files[0]) {
                const fd = new FormData();
                fd.append('image', imageInput.files[0]);
                try {
                    const imgRes = await fetch(`/api/capteur-points/${data.id}/image`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF },
                        body: fd,
                    });
                    if (imgRes.ok) {
                        const imgData = await imgRes.json();
                        data.image_url = imgData.image_url;
                    }
                } catch { /* non-bloquant */ }
            }
            // Si la section 3 avait été acquittée pour ce nouveau point, persister l'ack avec le vrai ID
            if (document.getElementById('point-alerts-section')?.classList.contains('hidden')) {
                localStorage.setItem(`flags_ack_${data.id}`, '1');
            }
            addPointMarker(data);
        } else {
            const marker = pointsOnMap[editingPoint.id];
            if (marker) {
                // Fusionner les données retournées avec celles en mémoire pour garder icu_value si inchangée
                const merged = { ...editingPoint, ...data };
                marker.setIcon(buildPointIcon(merged));
                marker.setPopupContent(pointPopupContent(merged));
            }
        }

        pendingPoint = null;
        window.closePointModal();
    } catch (err) {
        console.error(err);
        alert("Erreur lors de l'enregistrement.");
    } finally {
        if (saveBtn) {
            saveBtn.disabled    = false;
            saveBtn.textContent = editingPoint ? 'Mettre à jour' : 'Valider';
        }
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

    const owner = isOwner(p);

    return `
        <div style="min-width:180px;font-family:'Space Grotesk',sans-serif">
            <p style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px">🌡 ${p.name}</p>
            <p style="font-size:11px;color:#64748b;margin:0 0 2px">Témoin : ${p.temoin_name}</p>
            <p style="font-size:11px;color:#64748b;margin:0 0 10px">Posé par : ${p.user_name}</p>
            <p style="font-size:16px;font-weight:900;color:${color};margin:0 0 4px">ICU : ${icu}</p>
            ${warningHtml}
            <button onclick="window.openPointModal(JSON.parse(this.dataset.p))" data-p="${data}"
                style="flex:1;padding:6px 0;font-size:12px;font-weight:600;color:#fff;background:#0f172a;border:none;border-radius:8px;cursor:pointer;width:100%">
                ${owner ? 'Modifier' : 'Détail'}
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

    // Charger les points pour tout le monde
    fetch('/api/capteur-points')
        .then((r) => r.json())
        .then((points) => points.forEach(addPointMarker))
        .catch(() => {});

    // Clic pour créer — auth seulement
    if (document.body.dataset.auth !== '1') return;

    window._klymapInstance.on('click', (e) => {
        if (window._placementMode) return;
        onMapClickPoint(e.latlng);
    });
});

// Variable mémoire pour retenir le filtre actif
window.activeFilterNode = null;

window.toggleMapFilter = function(btnElement, min, max) {
    const allButtons = document.querySelectorAll('.legend-filter');
    const resetBtn = document.getElementById('btn-reset-filters');

    // 1. Si on clique sur le filtre DÉJÀ actif : on désactive tout
    if (window.activeFilterNode === btnElement) {
        window.resetMapFilters();
        return;
    }

    // 2. Sinon, on mémorise le clic et on affiche "Réinitialiser"
    window.activeFilterNode = btnElement;
    if (resetBtn) resetBtn.classList.remove('hidden');

    // 3. Changement d'apparence des boutons (Opacité à 40% pour les inactifs)
    allButtons.forEach(btn => {
        const dot = btn.querySelector('div');
        if (btn === btnElement) {
            btn.classList.remove('opacity-40');
            btn.classList.add('bg-teal-900/60', 'ring-1', 'ring-white/50');
            if (dot) dot.classList.add('scale-125');
        } else {
            btn.classList.remove('bg-teal-900/60', 'ring-1', 'ring-white/50');
            btn.classList.add('opacity-40');
            if (dot) dot.classList.remove('scale-125');
        }
    });

    // 4. Filtrage des points ICU sur la carte
    Object.values(pointsOnMap).forEach(marker => {
        if (!marker.pointData) return;
        const icu = marker.pointData.icu_value;
        let shouldShow = false;

        if (min === 'temoin') {
            shouldShow = (icu === null || icu === undefined);
        } else {
            if (icu !== null && icu >= min && icu < max) shouldShow = true;
        }

        if (shouldShow) {
            if (!window._klymapInstance.hasLayer(marker)) window._klymapInstance.addLayer(marker);
        } else {
            if (window._klymapInstance.hasLayer(marker)) window._klymapInstance.removeLayer(marker);
        }
    });

    // 5. Filtrage des capteurs témoins (s'ils existent sur la carte)
    if (typeof temoinsMarkers !== 'undefined') {
        Object.values(temoinsMarkers).forEach(marker => {
            let shouldShow = (min === 'temoin'); // Les témoins ne s'affichent que si on clique sur le filtre "temoin"
            if (shouldShow) {
                if (!window._klymapInstance.hasLayer(marker)) window._klymapInstance.addLayer(marker);
            } else {
                if (window._klymapInstance.hasLayer(marker)) window._klymapInstance.removeLayer(marker);
            }
        });
    }
};

window.resetMapFilters = function() {
    // On vide la mémoire
    window.activeFilterNode = null;

    const allButtons = document.querySelectorAll('.legend-filter');
    const resetBtn = document.getElementById('btn-reset-filters');

    if (resetBtn) resetBtn.classList.add('hidden');

    // On restaure l'apparence de tous les boutons
    allButtons.forEach(btn => {
        btn.classList.remove('bg-teal-900/60', 'ring-1', 'ring-white/50', 'opacity-40');
        const dot = btn.querySelector('div');
        if (dot) dot.classList.remove('scale-125');
    });

    // On réaffiche tous les points ICU
    Object.values(pointsOnMap).forEach(marker => {
        if (!window._klymapInstance.hasLayer(marker)) {
            window._klymapInstance.addLayer(marker);
        }
    });

    // On réaffiche tous les points témoins
    if (typeof temoinsMarkers !== 'undefined') {
        Object.values(temoinsMarkers).forEach(marker => {
            if (!window._klymapInstance.hasLayer(marker)) {
                window._klymapInstance.addLayer(marker);
            }
        });
    }
};
