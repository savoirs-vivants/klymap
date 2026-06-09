document.addEventListener('DOMContentLoaded', () => {
    const mapEl = document.getElementById('map');
    if (!mapEl) return;

    const map = window.L.map('map', {
        center: [48.5853, 7.7512],
        zoom: 15,
        zoomControl: false,
    });

    window._klymapInstance = map;

    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    let locationMarker = null;
    let locationCircle = null;

    const btnGeolocate = document.getElementById('btn-geolocate');

    if (btnGeolocate) {
        btnGeolocate.addEventListener('click', () => {
            if (!navigator.geolocation) return;

            btnGeolocate.classList.add('is-loading');

            navigator.geolocation.getCurrentPosition(
                ({ coords }) => {
                    const { latitude, longitude, accuracy } = coords;

                    if (locationMarker) {
                        locationMarker.remove();
                        locationCircle.remove();
                    }

                    locationCircle = window.L.circle([latitude, longitude], {
                        radius: accuracy,
                        color: '#0d9488',
                        fillColor: '#0d9488',
                        fillOpacity: 0.12,
                        weight: 1.5,
                    }).addTo(map);

                    locationMarker = window.L.circleMarker([latitude, longitude], {
                        radius: 8,
                        color: '#ffffff',
                        fillColor: '#0d9488',
                        fillOpacity: 1,
                        weight: 2.5,
                    }).addTo(map);

                    map.setView([latitude, longitude], 16);
                    btnGeolocate.classList.remove('is-loading');
                    btnGeolocate.classList.add('is-active');
                },
                () => {
                    btnGeolocate.classList.remove('is-loading');
                    alert("Impossible d'obtenir votre position.");
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    }

    document.dispatchEvent(new CustomEvent('klymap:ready'));

    // ── Recherche de ville / code postal via Nominatim ────────────────────────
    const cityInput   = document.getElementById('city-search-input');
    const resultsList = document.getElementById('city-search-results');

    if (cityInput && resultsList) {
        let debounceTimer = null;
        let currentResults = [];

        const goTo = (item) => {
            map.setView([parseFloat(item.lat), parseFloat(item.lon)], 13);
            cityInput.value = item.display_name.split(',').slice(0, 2).join(',').trim();
            resultsList.classList.add('hidden');
            resultsList.innerHTML = '';
        };

        const showResults = (results) => {
            currentResults = results;
            resultsList.innerHTML = '';
            if (!results.length) { resultsList.classList.add('hidden'); return; }
            results.forEach((item, i) => {
                const li = document.createElement('li');
                li.className = 'flex items-start gap-2.5 px-4 py-2.5 cursor-pointer hover:bg-teal-50 transition-colors text-sm';
                const parts = item.display_name.split(',');
                li.innerHTML = `
                    <svg class="w-3.5 h-3.5 text-teal-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>
                        <span class="font-semibold text-slate-800">${parts[0].trim()}</span>
                        <span class="text-slate-400 text-xs ml-1">${parts.slice(1, 3).join(',').trim()}</span>
                    </span>`;
                li.addEventListener('mousedown', (e) => { e.preventDefault(); goTo(item); });
                resultsList.appendChild(li);
            });
            resultsList.classList.remove('hidden');
        };

        const fetchSuggestions = async (q) => {
            try {
                const res  = await fetch(
                    `https://nominatim.openstreetmap.org/search?format=json&limit=6&addressdetails=1&q=${encodeURIComponent(q)}`,
                    { headers: { 'Accept-Language': 'fr' } }
                );
                showResults(await res.json());
            } catch { /* réseau indisponible */ }
        };

        cityInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const q = cityInput.value.trim();
            if (q.length < 2) { resultsList.classList.add('hidden'); return; }
            debounceTimer = setTimeout(() => fetchSuggestions(q), 300);
        });

        cityInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && currentResults.length) goTo(currentResults[0]);
            if (e.key === 'Escape') { resultsList.classList.add('hidden'); cityInput.blur(); }
        });

        cityInput.addEventListener('blur', () => {
            setTimeout(() => resultsList.classList.add('hidden'), 150);
        });
    }
});
