// ─── Marqueurs stations météo sur la carte ───────────────────────────────────

document.addEventListener('klymap:ready', () => {
    const map = window._klymapInstance;
    if (!map) return;

    fetch('/api/capteurs-map')
        .then((r) => r.json())
        .then((stations) => stations.forEach(buildCapteurStationMarker))
        .catch(() => {});
});

// Bluetooth modal open/close
const btModal = document.getElementById('bt-modal');
document.getElementById('btn-open-bt')?.addEventListener('click', () => btModal.classList.remove('translate-y-full'));
document.getElementById('btn-open-bt-desk')?.addEventListener('click', () => btModal.classList.remove('translate-y-full'));
document.getElementById('bt-close')?.addEventListener('click', () => btModal.classList.add('translate-y-full'));

// Sync recherche desktop → mobile.
// map.js écoute uniquement #search-input (dans la top-bar mobile), mais sur desktop
// la recherche visible est #search-input-desk (dans la sidebar). On proxy les events
// et on miroir les résultats via MutationObserver pour que les deux restent cohérents.
const deskSearch    = document.getElementById('search-input-desk');
const mobileSearch  = document.getElementById('search-input');
const deskResults   = document.getElementById('search-results-desk');
const mobileResults = document.getElementById('search-results');

if (deskSearch && mobileSearch) {
    deskSearch.addEventListener('input', () => {
        mobileSearch.value = deskSearch.value;
        mobileSearch.dispatchEvent(new Event('input', { bubbles: true }));
    });
    const observer = new MutationObserver(() => {
        deskResults.innerHTML = mobileResults.innerHTML;
        deskResults.classList.toggle('hidden', mobileResults.classList.contains('hidden'));
    });
    observer.observe(mobileResults, { childList: true, attributes: true, attributeFilter: ['class'] });
}

// ─── Bluetooth PCB ────────────────────────────────────────────────────────────

const zoneLog     = document.getElementById('bt-console');
const btnConnect  = document.getElementById('bt-action-connect');
const btnDownload = document.getElementById('bt-action-download');
const btnSync     = document.getElementById('bt-action-sync');
const syncStatus  = document.getElementById('bt-sync-status');

// UUIDs du module RN4871 (UART Bluetooth LE) -> remplacer par les valeurs de la station météo
// const RN4871_SERVICE_UUID = '';
// const RN4871_TX_UUID      = '';
// const RN4871_RX_UUID      = '';

let receiveBuffer       = "";
let writeCharacteristic = null;

function ecrireSysteme(message) {
    const temps = new Date().toLocaleTimeString();
    zoneLog.value += `\n\n[${temps}] 🔵 ${message}\n`;
    zoneLog.scrollTop = zoneLog.scrollHeight;
}

// Format des lignes reçues du capteur :
//   UID:005D003D393650022037374E          → identifiant unique du capteur (clé de lookup en BDD)
//   1,268435456,21.5,63,12,1013,0,30.2,5.1,1.2,2.4 → id, timestamp_unix, temp, hum, vitesse_vent, press_baro, pluie, indice_chaleur, debit_pluie, densite_air, evapotranspiration
function decodeData(line) {
    if (line.startsWith('UID:')) {
        document.getElementById('valUid').innerText = line.substring(4).trim();
        return;
    }
    const parts = line.split(',');
    if (parts.length < 3) return;
    if (parts[2] !== undefined) document.getElementById('valTemp').innerText              = parts[2] + ' °C';
    if (parts[3] !== undefined) document.getElementById('valHum').firstChild.textContent  = parts[3] + ' ';
    if (parts[4] !== undefined) document.getElementById('valVent').firstChild.textContent = parts[4] + ' ';
    if (parts[5] !== undefined) document.getElementById('valPress').firstChild.textContent = parts[5] + ' ';
    if (parts[6] !== undefined) document.getElementById('valPluie').firstChild.textContent = parts[6] + ' ';
    if (parts[7] !== undefined) document.getElementById('valIndiceChaleur').firstChild.textContent = parts[7] + ' ';
    if (parts[8] !== undefined) document.getElementById('valDebitPluie').firstChild.textContent = parts[8] + ' ';
    if (parts[9] !== undefined) document.getElementById('valDensiteAir').firstChild.textContent = parts[9] + ' ';
    if (parts[10] !== undefined) document.getElementById('valEvapotranspiration').firstChild.textContent = parts[10] + ' ';
}

// Parse l'intégralité de la console au moment du sync plutôt qu'en mémoire en temps réel.
// Raison : avec 4000+ lignes, maintenir un tableau en parallèle doublerait la mémoire utilisée.
// On reparse le log texte (déjà en mémoire dans le textarea) à la demande, c'est négligeable.
// Le Set `seen` gère le distinct côté client avant l'envoi — le serveur fait aussi sa propre
// vérification sur les timestamps déjà en BDD.
function parseLog(logText) {
    const lines  = logText.split('\n');
    let uid      = null;
    const lignes = [];
    const seen   = new Set();

    for (const raw of lines) {
        const line = raw.trim();
        if (!line || line.startsWith('[') || line.startsWith('Prêt')) continue;

        if (line.startsWith('UID:')) {
            uid = line.substring(4).trim();
            continue;
        }

        const parts = line.split(',');
        if (parts.length < 3) continue;

        const ts = parseInt(parts[1]);
        if (isNaN(ts) || ts <= 0) continue;
        if (seen.has(ts)) continue;
        seen.add(ts);

        lignes.push({
            timestamp:          ts,
            temp:               parts[2]  !== undefined && parts[2]  !== '' ? parseFloat(parts[2])  : null,
            hum:                parts[3]  !== undefined && parts[3]  !== '' ? parseFloat(parts[3])  : null,
            vitesse_vent:       parts[4]  !== undefined && parts[4]  !== '' ? parseFloat(parts[4])  : null,
            press_baro:         parts[5]  !== undefined && parts[5]  !== '' ? parseFloat(parts[5])  : null,
            pluie:              parts[6]  !== undefined && parts[6]  !== '' ? parseFloat(parts[6])  : null,
            indice_chaleur:     parts[7]  !== undefined && parts[7]  !== '' ? parseFloat(parts[7])  : null,
            debit_pluie:        parts[8]  !== undefined && parts[8]  !== '' ? parseFloat(parts[8])  : null,
            densite_air:        parts[9]  !== undefined && parts[9]  !== '' ? parseFloat(parts[9])  : null,
            evapotranspiration: parts[10] !== undefined && parts[10] !== '' ? parseFloat(parts[10]) : null,
        });
    }

    return { uid, lignes };
}

async function sendCommandToPCB(cmd) {
    if (!writeCharacteristic) { ecrireSysteme("❌ Connectez d'abord le PCB."); return; }
    try {
        await writeCharacteristic.writeValue(new TextEncoder('utf-8').encode(cmd));
        ecrireSysteme(`👉 Commande : "${cmd}"`);
    } catch (e) { ecrireSysteme(`❌ ${e.message}`); }
}

document.getElementById('bt-action-start')?.addEventListener('click', () => sendCommandToPCB("?"));
document.getElementById('bt-action-stop')?.addEventListener('click', () => sendCommandToPCB("Q"));

btnConnect?.addEventListener('click', async () => {
    try {
        ecrireSysteme("Recherche d'une station Bluetooth...");
        const device = await navigator.bluetooth.requestDevice({
            filters: [{ namePrefix: 'Station' }, { namePrefix: 'station' }],
            optionalServices: [RN4871_SERVICE_UUID]
        });
        ecrireSysteme(`Connecté à : ${device.name}`);
        btnConnect.innerText = "Connecté ✅";
        btnConnect.classList.replace('bg-sv-blue', 'bg-[#16987c]');
        device.addEventListener('gattserverdisconnected', () => {
            ecrireSysteme("Déconnecté.");
            btnConnect.innerText = "1. Connecter le PCB";
            btnConnect.classList.replace('bg-[#16987c]', 'bg-sv-blue');
            writeCharacteristic = null;
        });
        const server   = await device.gatt.connect();
        const service  = await server.getPrimaryService(RN4871_SERVICE_UUID);
        const readChar = await service.getCharacteristic(RN4871_TX_UUID);
        writeCharacteristic = await service.getCharacteristic(RN4871_RX_UUID);
        ecrireSysteme("Écoute et écriture activées.");
        await readChar.startNotifications();
        readChar.addEventListener('characteristicvaluechanged', (event) => {
            const text = new TextDecoder('utf-8').decode(event.target.value);
            zoneLog.value += text;
            zoneLog.scrollTop = zoneLog.scrollHeight;
            receiveBuffer += text;
            let lines = receiveBuffer.split('\n');
            receiveBuffer = lines.pop();
            for (let line of lines) { line = line.trim(); if (line) decodeData(line); }
        });
    } catch (e) {
        if (e.name !== 'NotFoundError') ecrireSysteme(`ERREUR : ${e.message}`);
        else ecrireSysteme("Recherche annulée.");
    }
});

btnDownload?.addEventListener('click', () => {
    const blob = new Blob([zoneLog.value], { type: 'text/plain' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `Log_Station_${new Date().toISOString().slice(0, 10)}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
});

btnSync?.addEventListener('click', async () => {
    const { uid, lignes } = parseLog(zoneLog.value);

    if (!uid) {
        syncStatus.className   = 'mt-2 p-3 rounded-xl text-xs font-mono bg-red-50 text-red-600';
        syncStatus.textContent = '❌ Aucun UID détecté dans les données reçues.';
        syncStatus.classList.remove('hidden');
        return;
    }
    if (lignes.length === 0) {
        syncStatus.className   = 'mt-2 p-3 rounded-xl text-xs font-mono bg-amber-50 text-amber-600';
        syncStatus.textContent = '⚠️ Aucune ligne de données à synchroniser.';
        syncStatus.classList.remove('hidden');
        return;
    }

    btnSync.disabled       = true;
    btnSync.textContent    = `Envoi de ${lignes.length} mesures…`;
    syncStatus.className   = 'mt-2 p-3 rounded-xl text-xs font-mono bg-slate-100 text-slate-600';
    syncStatus.textContent = '⏳ Synchronisation en cours…';
    syncStatus.classList.remove('hidden');

    try {
        // L'URL est injectée via window.btSyncUrl dans la blade (seul endroit avec accès à PHP)
        const resp = await fetch(window.btSyncUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ uid, lignes }),
        });

        const data = await resp.json();

        if (resp.ok) {
            syncStatus.className   = 'mt-2 p-3 rounded-xl text-xs font-mono bg-emerald-50 text-emerald-700';
            syncStatus.textContent = `✅ ${data.inseres} mesures insérées · ${data.ignores} ignorées (déjà en BDD)`;
            ecrireSysteme(`✅ Sync BDD : ${data.inseres} insérées, ${data.ignores} ignorées.`);
        } else {
            syncStatus.className   = 'mt-2 p-3 rounded-xl text-xs font-mono bg-red-50 text-red-600';
            syncStatus.textContent = `❌ ${data.error || 'Erreur serveur'}`;
        }
    } catch (e) {
        syncStatus.className   = 'mt-2 p-3 rounded-xl text-xs font-mono bg-red-50 text-red-600';
        syncStatus.textContent = `❌ Erreur réseau : ${e.message}`;
    } finally {
        btnSync.disabled  = false;
        btnSync.innerHTML = `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg> Synchroniser avec la BDD`;
    }
});

// ─── Overlay : Localiser un capteur par DevEui ────────────────────────────────

function buildCapteurStationMarker(s) {
    const map = window._klymapInstance;
    if (!map) return;
    const icon = window.L.divIcon({
        className: '',
        html: `<div style="width:36px;height:36px;border-radius:50%;background:#2563eb;border:3px solid #fff;box-shadow:0 2px 8px rgba(37,99,235,0.45);display:flex;align-items:center;justify-content:center;">
            <svg width="16" height="16" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
            </svg></div>`,
        iconSize: [36, 36], iconAnchor: [18, 18],
    });
    const lines = [
        `<strong>${s.uid ?? s.deveui ?? 'Station #' + s.id}</strong>`,
        s.temp               != null ? `🌡 ${s.temp} °C` : null,
        s.hum                != null ? `💧 ${s.hum} %` : null,
        s.vitesse_vent       != null ? `💨 ${s.vitesse_vent} km/h` : null,
        s.press_baro         != null ? `📊 ${s.press_baro} hPa` : null,
        s.pluie              != null ? `🌧 ${s.pluie} mm` : null,
        s.indice_chaleur     != null ? `🥵 ${s.indice_chaleur} °C (ressenti)` : null,
        s.debit_pluie        != null ? `☔ ${s.debit_pluie} mm/h` : null,
        s.densite_air        != null ? `🌬 ${s.densite_air} kg/m³` : null,
        s.evapotranspiration != null ? `🌱 ${s.evapotranspiration} mm (évapotranspiration)` : null,
        s.updated_at         != null ? `<em class="text-slate-400">${s.updated_at}</em>` : null,
        `<a href="${s.show_url}" style="color:#2563eb;font-weight:600;">Voir l'historique →</a>`,
    ].filter(Boolean).join('<br>');
    window.L.marker([s.lat, s.lng], { icon }).addTo(map).bindPopup(lines, { maxWidth: 240 });
}

window.openCapteurLocateModal = function (lat, lng) {
    const overlay = document.getElementById('overlay-capteur-locate');
    if (!overlay) return;
    document.getElementById('capteur-locate-deveui').value = '';
    document.getElementById('capteur-locate-lat').value    = lat ?? '';
    document.getElementById('capteur-locate-lng').value    = lng ?? '';
    const coords = document.getElementById('capteur-locate-coords');
    if (coords) coords.textContent = lat != null ? `${lat.toFixed(6)}, ${lng.toFixed(6)}` : '—';
    const err = document.getElementById('capteur-locate-error');
    err.textContent = ''; err.classList.add('hidden');
    overlay.classList.remove('hidden');
};

window.closeCapteurLocateModal = function () {
    document.getElementById('overlay-capteur-locate')?.classList.add('hidden');
};

document.getElementById('capteur-locate-save-btn')?.addEventListener('click', async () => {
    const deveui = document.getElementById('capteur-locate-deveui').value.trim();
    const lat    = document.getElementById('capteur-locate-lat').value.trim();
    const lng    = document.getElementById('capteur-locate-lng').value.trim();
    const err    = document.getElementById('capteur-locate-error');
    const btn    = document.getElementById('capteur-locate-save-btn');

    err.classList.add('hidden');

    if (!deveui || !lat || !lng) {
        err.textContent = 'Tous les champs sont obligatoires.';
        err.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Enregistrement…';

    try {
        const resp = await fetch('/api/capteurs/locate', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ DevEui: deveui, lat: parseFloat(lat), long: parseFloat(lng) }),
        });

        const data = await resp.json();

        if (!resp.ok) {
            err.textContent = data.error || 'Erreur serveur.';
            err.classList.remove('hidden');
            return;
        }

        buildCapteurStationMarker(data);
        window.closeCapteurLocateModal();

        // Centrer la carte sur le nouveau marqueur
        window._klymapInstance?.setView([data.lat, data.lng], 15);

    } catch (e) {
        err.textContent = 'Erreur réseau : ' + e.message;
        err.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Valider';
    }
});
