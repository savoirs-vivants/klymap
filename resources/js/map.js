document.addEventListener('DOMContentLoaded', () => {
    const mapEl = document.getElementById('map');
    if (!mapEl) return;

    const map = window.L.map('map', {
        center: [48.5853, 7.7512],
        zoom: 15,
        zoomControl: true,
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
});
