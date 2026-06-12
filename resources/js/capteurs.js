const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let placementMode  = null;
let pendingLatLng  = null;
let temoinsMarkers = {};
let editingTemoin  = null;
let parsedMesures  = [];
let pendingTemoinFiles = [];

/* ══════════════════════════════════
   Aside — menu capteurs
══════════════════════════════════ */

window.toggleCapteursMenu = function () {
    const sub      = document.getElementById('capteurs-submenu');
    const chevron  = document.getElementById('capteurs-chevron');
    if (!sub) return;
    const open = !sub.classList.contains('hidden');
    sub.classList.toggle('hidden', open);
    chevron?.classList.toggle('rotate-180', !open);
};

/* ══════════════════════════════════
   Mode placement
══════════════════════════════════ */

window.activatePlacementMode = function (type) {
    placementMode = type;
    window._placementMode = type;

    const banner = document.getElementById('placement-banner');
    if (!banner) return;

    banner.classList.remove('hidden');
    banner.classList.add('flex');

    const msg = document.getElementById('placement-message');
    if (msg) msg.textContent = type === 'capteur'
        ? 'Cliquez sur la carte pour placer le capteur'
        : type === 'point'
        ? 'Cliquez sur la carte pour placer le capteur urbain'
        : 'Cliquez sur la carte pour placer le capteur témoin';

    if (window._klymapInstance) {
        window._klymapInstance.getContainer().style.cursor = 'crosshair';
    }
};

window.cancelPlacementMode = function () {
    placementMode = null;
    window._placementMode = null;
    pendingLatLng = null;

    const banner = document.getElementById('placement-banner');
    banner?.classList.add('hidden');
    banner?.classList.remove('flex');

    if (window._klymapInstance) {
        window._klymapInstance.getContainer().style.cursor = '';
    }
};

/* ══════════════════════════════════
   Overlay témoin
══════════════════════════════════ */

/* ══════════════════════════════════
   Galerie d'images — capteur témoin
══════════════════════════════════ */

function renderTemoinGallery(images) {
    const gallery = document.getElementById('temoin-images-gallery');
    if (!gallery) return;

    gallery.querySelectorAll('.temoin-image-thumb').forEach((el) => el.remove());

    (images ?? []).forEach((img) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'temoin-image-thumb relative w-24 h-24 shrink-0';

        const thumb = document.createElement('img');
        thumb.src = img.url;
        thumb.alt = 'Photo capteur témoin';
        thumb.className = 'w-24 h-24 object-cover rounded-xl border border-slate-200 cursor-zoom-in';
        thumb.onclick = () => openLightbox(img.url);
        wrapper.appendChild(thumb);

        if (editingTemoin) {
            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'absolute -top-2 -right-2 w-6 h-6 flex items-center justify-center bg-red-500 hover:bg-red-600 text-white rounded-full shadow text-xs font-bold transition-colors';
            delBtn.innerHTML = '&times;';
            delBtn.onclick = async (e) => {
                e.preventDefault();
                if (!confirm('Supprimer cette photo ?')) return;
                try {
                    const r = await fetch(`/api/capteur-temoins/${editingTemoin.id}/image`, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ path: img.path }),
                    });
                    if (!r.ok) throw new Error();
                    const data = await r.json();
                    editingTemoin.images = data.images;
                    renderTemoinGallery(data.images);
                } catch { alert('Erreur lors de la suppression de la photo.'); }
            };
            wrapper.appendChild(delBtn);
        }

        gallery.insertBefore(wrapper, gallery.firstChild);
    });
}

function openLightbox(src) {
    const lb  = document.getElementById('img-lightbox');
    const img = document.getElementById('img-lightbox-src');
    if (!lb || !img || !src) return;
    img.src = src;
    lb.classList.remove('hidden');
}

window.openTemoinOverlay = function (temoin = null) {
    editingTemoin = temoin;
    parsedMesures = [];

    const overlay = document.getElementById('overlay-temoin');
    if (!overlay) return;

    document.getElementById('temoin-name').value       = temoin?.name ?? '';
    document.getElementById('temoin-file-input').value = '';
    document.getElementById('temoin-preview').classList.add('hidden');

    // Date (1ère mesure du jeu de données)
    const dateSection = document.getElementById('temoin-date-section');
    const dateInput   = document.getElementById('temoin-date');
    if (dateInput) dateInput.value = temoin?.date ?? '';
    dateSection?.classList.toggle('hidden', !temoin?.date);

    // Photos du capteur
    const imgInput = document.getElementById('temoin-image-input');
    if (imgInput) imgInput.value = '';
    pendingTemoinFiles = [];
    renderTemoinGallery(temoin?.images);

    if (imgInput && !imgInput._listenerAdded) {
        imgInput._listenerAdded = true;
        imgInput.addEventListener('change', async () => {
            const files = Array.from(imgInput.files ?? []);
            if (!files.length) return;

            if (!editingTemoin) {
                // Pas encore créé : accumuler les fichiers et prévisualiser tout, upload différé après la création
                pendingTemoinFiles = pendingTemoinFiles.concat(files);
                imgInput.value = '';
                const previews = pendingTemoinFiles.map((f) => ({ url: URL.createObjectURL(f), path: null }));
                renderTemoinGallery(previews);
                return;
            }

            const fd = new FormData();
            files.forEach((f) => fd.append('images[]', f));
            try {
                const r = await fetch(`/api/capteur-temoins/${editingTemoin.id}/image`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    body: fd,
                });
                if (!r.ok) throw new Error();
                const { images } = await r.json();
                editingTemoin.images = images;
                renderTemoinGallery(images);
                imgInput.value = '';
            } catch { alert("Erreur lors de l'upload des images."); }
        });
    }

    const exportBtn   = document.getElementById('temoin-export-btn');
    exportBtn.classList.add('hidden');
    exportBtn.classList.remove('flex');

    const deleteBtn   = document.getElementById('temoin-delete-btn');
    const isEdit      = !!temoin;

    deleteBtn.classList.toggle('hidden', !isEdit);
    deleteBtn.classList.toggle('flex',    isEdit);

    document.getElementById('temoin-title').textContent =
        isEdit ? 'Modifier le capteur témoin' : 'Nouveau capteur témoin';
    document.getElementById('temoin-mesures-count').textContent =
        isEdit ? `${temoin.mesures_count} mesure(s) enregistrée(s)` : '';

    document.getElementById('temoin-file-label').textContent =
        isEdit ? 'Ajouter un nouveau fichier (.txt)' : 'Fichier de données (.txt)';
    document.getElementById('temoin-file-cta').textContent =
        isEdit ? 'Cliquez pour ajouter des données supplémentaires' : 'Cliquez pour choisir un fichier';

    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
};

window.closeTemoinOverlay = function () {
    const overlay = document.getElementById('overlay-temoin');
    overlay?.classList.add('hidden');
    overlay?.classList.remove('flex');
    editingTemoin = null;
    parsedMesures = [];
    pendingTemoinFiles = [];
    pendingLatLng = null;
};

/* ══════════════════════════════════
   Parsing fichier .txt
══════════════════════════════════ */

// Accepte aussi bien l'ancien format "sht_temp=13.81" (point décimal) que le
// nouveau format de données traitées "sht_temp 23,55" (sans égal, virgule décimale).
function parseDecimal(str) {
    return parseFloat(str.replace(',', '.'));
}

function parseTxtFile(text) {
    const result = [];
    for (const line of text.trim().split('\n')) {
        const m = line.match(
            /^\d+\s+(\d{4}\/\d{1,2}\/\d{1,2})\s+(\d{2}:\d{2}:\d{2}).*sht_temp[=\s]+([\d.,]+).*sht_hum[=\s]+([\d.,]+).*tmp_temp[=\s]+([\d.,]+)/
        );
        if (!m) continue;
        const [, date, time, sht_temp, sht_hum, tmp_temp] = m;
        const [y, mo, d] = date.split('/');
        result.push({
            enregistre_le: `${y}-${mo.padStart(2, '0')}-${d.padStart(2, '0')} ${time}`,
            sht_temp:  parseDecimal(sht_temp),
            sht_hum:   parseDecimal(sht_hum),
            tmp_temp:  parseDecimal(tmp_temp),
        });
    }
    return result;
}

function renderPreview(mesures) {
    const tbody = document.getElementById('temoin-tbody');
    tbody.innerHTML = '';

    mesures.slice(0, 100).forEach((m, i) => {
        const tr      = document.createElement('tr');
        tr.className  = i % 2 === 1 ? 'bg-slate-50' : '';
        tr.innerHTML  = `
            <td class="px-3 py-2 text-slate-700 whitespace-nowrap text-xs">${m.enregistre_le}</td>
            <td class="px-3 py-2 text-right text-slate-700 whitespace-nowrap text-xs">${m.sht_temp}</td>
            <td class="px-3 py-2 text-right text-slate-700 whitespace-nowrap text-xs">${m.sht_hum}</td>
            <td class="px-3 py-2 text-right text-slate-700 whitespace-nowrap text-xs">${m.tmp_temp}</td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('temoin-row-count').textContent =
        `${mesures.length} ligne(s)${mesures.length > 100 ? ' — aperçu limité à 100' : ''}`;

    document.getElementById('temoin-preview').classList.remove('hidden');

    const exportBtn = document.getElementById('temoin-export-btn');
    exportBtn.classList.remove('hidden');
    exportBtn.classList.add('flex');
}

/* ══════════════════════════════════
   Export CSV local
══════════════════════════════════ */

function exportLocalExcel() {
    const esc  = (v) => String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const name = document.getElementById('temoin-name')?.value.trim() || 'Aperçu';

    const header = ['Date / Heure','Temp. SHT (°C)','Humidité SHT (%)','Temp. TMP (°C)']
        .map((h) => `<Cell><Data ss:Type="String">${esc(h)}</Data></Cell>`).join('');

    const dataRows = parsedMesures.map((m) => {
        const cells = [
            `<Cell><Data ss:Type="String">${esc(m.enregistre_le)}</Data></Cell>`,
            `<Cell><Data ss:Type="Number">${m.sht_temp}</Data></Cell>`,
            `<Cell><Data ss:Type="Number">${m.sht_hum}</Data></Cell>`,
            `<Cell><Data ss:Type="Number">${m.tmp_temp}</Data></Cell>`,
        ].join('');
        return `<Row>${cells}</Row>`;
    }).join('');

    const xml = `<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
  <Worksheet ss:Name="${esc(name)}">
    <Table>
      <Row>${header}</Row>
      ${dataRows}
    </Table>
  </Worksheet>
</Workbook>`;

    const blob = new Blob([xml], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const a    = Object.assign(document.createElement('a'), {
        href:     URL.createObjectURL(blob),
        download: `temoin_preview_${Date.now()}.xls`,
    });
    a.click();
    URL.revokeObjectURL(a.href);
}

/* ══════════════════════════════════
   Markers carte
══════════════════════════════════ */

function popupContent(t) {
    const data    = JSON.stringify(t).replace(/"/g, '&quot;');
    const isAuth  = document.body.dataset.auth === '1';
    const buttons = isAuth ? `
        <div style="display:flex;gap:8px;margin-top:10px">
            <button onclick="window.openTemoinOverlay(JSON.parse(this.dataset.t))" data-t="${data}" class="temoin-popup-btn">Modifier</button>
            <button onclick="window.deleteTemoin(${t.id})" style="padding:5px 12px;background:#fee2e2;color:#dc2626;font-size:12px;font-weight:600;border:none;border-radius:8px;cursor:pointer">Supprimer</button>
        </div>` : '';
    return `
        <div style="min-width:160px;font-family:'Space Grotesk',sans-serif">
            <p style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px">📍 ${t.name}</p>
            <p style="font-size:11px;color:#64748b;margin:0">${t.mesures_count} mesure(s)</p>
            ${buttons}
        </div>`;
}

window.deleteTemoin = async function (id) {
    if (!confirm('Supprimer ce capteur témoin et toutes ses mesures ?')) return;

    try {
        const res = await fetch(`/api/capteur-temoins/${id}`, {
            method:  'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF },
        });
        if (!res.ok) throw new Error();

        temoinsMarkers[id]?.remove();
        delete temoinsMarkers[id];
        window.closeTemoinOverlay();
    } catch {
        alert("Erreur lors de la suppression.");
    }
};

function addTemoinMarker(t) {
    if (!window._klymapInstance) return;
    const icon = window.L.divIcon({
        className: '',
        html: '<div style="width:24px; height:24px; border-radius:50%; background:#0f172a; border:3px solid #fff; box-shadow:0 4px 12px rgba(0,0,0,0.4);"></div>',
        iconSize:  [24, 24],
        iconAnchor:[12, 12],
    });

    const marker = window.L.marker([t.lat, t.lng], { icon })
        .addTo(window._klymapInstance)
        .bindPopup(popupContent(t));
    marker.pointData = t;
    marker.isTemoin = true;

    temoinsMarkers[t.id] = marker;
}

/* ══════════════════════════════════
   Save
══════════════════════════════════ */

async function saveTemoin() {
    const name = document.getElementById('temoin-name').value.trim();
    if (!name) { document.getElementById('temoin-name').focus(); return; }
    if (!editingTemoin && parsedMesures.length === 0) {
        alert('Veuillez importer un fichier de données.');
        return;
    }

    const btn = document.getElementById('temoin-save-btn');
    btn.disabled    = true;
    btn.textContent = 'Enregistrement…';

    try {
        const isEdit = !!editingTemoin;
        const url    = isEdit ? `/api/capteur-temoins/${editingTemoin.id}` : '/api/capteur-temoins';
        const body   = isEdit
            ? { name, mesures: parsedMesures }
            : { name, lat: pendingLatLng.lat, lng: pendingLatLng.lng, mesures: parsedMesures };

        const res = await fetch(url, {
            method:  isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body:    JSON.stringify(body),
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        if (!isEdit) {
            // Upload des images si certaines ont été sélectionnées (le témoin vient d'être créé → on a maintenant son id)
            if (pendingTemoinFiles.length) {
                const fd = new FormData();
                pendingTemoinFiles.forEach((f) => fd.append('images[]', f));
                try {
                    const imgRes = await fetch(`/api/capteur-temoins/${data.id}/image`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF },
                        body: fd,
                    });
                    if (imgRes.ok) {
                        const imgData = await imgRes.json();
                        data.images = imgData.images;
                    }
                } catch { /* non-bloquant */ }
            }
            addTemoinMarker(data);
        } else {
            editingTemoin.name          = name;
            editingTemoin.date          = data.date ?? editingTemoin.date;
            editingTemoin.mesures_count += parsedMesures.length;
            temoinsMarkers[editingTemoin.id]?.setPopupContent(popupContent(editingTemoin));
        }

        window.closeTemoinOverlay();
    } catch (err) {
        console.error(err);
        alert("Erreur lors de l'enregistrement.");
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Valider';
    }
}

/* ══════════════════════════════════
   Init
══════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('temoin-file-input')?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
            parsedMesures = parseTxtFile(ev.target.result);
            if (!parsedMesures.length) { alert('Aucune donnée reconnue.'); return; }
            renderPreview(parsedMesures);
        };
        reader.readAsText(file);
    });

    document.getElementById('temoin-export-btn')?.addEventListener('click', () => {
        if (editingTemoin) {
            window.location.href = `/api/capteur-temoins/${editingTemoin.id}/export`;
        } else {
            exportLocalExcel();
        }
    });

    document.getElementById('temoin-save-btn')?.addEventListener('click', saveTemoin);

    document.getElementById('temoin-delete-btn')?.addEventListener('click', () => {
        if (editingTemoin) window.deleteTemoin(editingTemoin.id);
    });
});

document.addEventListener('klymap:ready', () => {
    if (!window._klymapInstance) return;

    // Charger les témoins pour tout le monde
    fetch('/api/capteur-temoins')
        .then((r) => r.json())
        .then((temoins) => temoins.forEach(addTemoinMarker))
        .catch(() => {});

    // Actions d'édition réservées aux auth
    if (document.body.dataset.auth !== '1') return;

    window._klymapInstance.on('click', (e) => {
        if (!placementMode) return;

        if (placementMode === 'temoin') {
            pendingLatLng = e.latlng;
            const banner = document.getElementById('placement-banner');
            banner?.classList.add('hidden');
            banner?.classList.remove('flex');
            placementMode = null;
            window._placementMode = null;
            if (window._klymapInstance) {
                window._klymapInstance.getContainer().style.cursor = '';
            }
            window.openTemoinOverlay(null);
        } else if (placementMode === 'capteur') {
            const banner = document.getElementById('placement-banner');
            banner?.classList.add('hidden');
            banner?.classList.remove('flex');
            placementMode = null;
            window._placementMode = null;
            if (window._klymapInstance) {
                window._klymapInstance.getContainer().style.cursor = '';
            }
            window.openCapteurLocateModal(e.latlng.lat, e.latlng.lng);
        }
    });
});
