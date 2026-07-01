(function () {
    'use strict';

    var el = document.getElementById('boundary-map');
    var form = document.getElementById('boundary-form');
    var input = document.getElementById('polygon_geojson');
    var areaEl = document.getElementById('boundary-area');
    if (!el || !form || !input || typeof L === 'undefined' || !window.FarmQBoundary) {
        return;
    }

    var cfg = window.FarmQBoundary;
    var map = L.map(el).setView([cfg.center.lat, cfg.center.lng], 13);
    var drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    if (cfg.polygon && cfg.polygon.coordinates) {
        var existing = L.geoJSON({ type: 'Feature', geometry: cfg.polygon });
        existing.eachLayer(function (layer) {
            drawnItems.addLayer(layer);
        });
        try {
            map.fitBounds(drawnItems.getBounds(), { padding: [24, 24] });
        } catch (e) { /* empty */ }
        syncInput();
    }

    var drawControl = new L.Control.Draw({
        draw: {
            polygon: { allowIntersection: false, showArea: true },
            polyline: false,
            rectangle: false,
            circle: false,
            marker: false,
            circlemarker: false
        },
        edit: { featureGroup: drawnItems }
    });
    map.addControl(drawControl);

    map.on(L.Draw.Event.CREATED, function (event) {
        drawnItems.clearLayers();
        drawnItems.addLayer(event.layer);
        syncInput();
    });

    map.on(L.Draw.Event.EDITED, syncInput);
    map.on(L.Draw.Event.DELETED, function () {
        input.value = '';
        if (areaEl) {
            areaEl.textContent = '';
        }
    });

    function syncInput() {
        var layers = drawnItems.getLayers();
        if (!layers.length) {
            return;
        }
        var geojson = layers[0].toGeoJSON();
        input.value = JSON.stringify(geojson);
        if (areaEl && typeof L.GeometryUtil !== 'undefined') {
            var areaM2 = L.GeometryUtil.geodesicArea(geojson.geometry.coordinates[0]);
            var areaHa = (areaM2 / 10000).toFixed(2);
            areaEl.textContent = cfg.labels.area.replace(':area', areaHa);
        }
    }

    form.addEventListener('submit', function (event) {
        if (!input.value) {
            event.preventDefault();
            alert(cfg.labels.draw);
        }
    });
})();
